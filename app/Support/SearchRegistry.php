<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SearchRegistry
{
    public function __construct(protected ModuleRegistry $modules) {}

    /**
     * Modules that opt into Master Search via their module.php manifest.
     *
     * Manifest must declare:
     *   'searchable' => [
     *       'model' => CustomerMaster::class,
     *       'label' => 'Customers',          // optional, defaults to module label
     *       'icon'  => 'user-circle',        // optional, defaults to module icon
     *       'route' => 'customer-master.index', // optional, defaults to module's first menu route
     *   ],
     */
    public function sources(): Collection
    {
        $user = auth()->user();

        return $this->modules->all()
            ->filter(fn (array $m) => is_array($m['searchable'] ?? null) && ! empty($m['searchable']['model']))
            ->map(function (array $m) {
                $s = $m['searchable'];

                return [
                    'module' => $m['name'],
                    'model' => $s['model'],
                    'label' => $s['label'] ?? $m['label'],
                    'icon' => $s['icon'] ?? $m['icon'],
                    'route' => $s['route'] ?? null,
                    'permission' => $s['permission'] ?? $this->derivePermission($m['name']),
                ];
            })
            ->filter(function (array $source) use ($user) {
                if ($source['permission'] === null) {
                    return true;
                }
                if (! $user) {
                    return false;
                }

                return $user->can($source['permission']);
            })
            ->values();
    }

    /**
     * Default search permission for a module: `{snake_module}.view` if it appears in the
     * module's permission list. Returns null if no view permission is declared.
     */
    protected function derivePermission(string $moduleName): ?string
    {
        $module = $this->modules->get($moduleName);
        if (! $module) {
            return null;
        }
        $snake = Str::snake($moduleName);
        $candidate = $snake.'.view';
        $permissions = (array) ($module['permissions'] ?? []);

        return in_array($candidate, $permissions, true) ? $candidate : null;
    }

    /**
     * Run the search across every registered source. Returns:
     *   [['module', 'label', 'icon', 'route', 'count', 'rows' => [{id,title,subtitle,route}, ...]], ...]
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $term, int $perSource = 5): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $results = [];

        foreach ($this->sources() as $source) {
            /** @var class-string<Model> $modelClass */
            $modelClass = $source['model'];
            if (! class_exists($modelClass) || ! method_exists($modelClass, 'scopeSearch')) {
                continue;
            }

            try {
                $rows = $modelClass::query()
                    ->search($term)
                    ->limit($perSource)
                    ->get()
                    ->map(fn (Model $row) => array_merge(
                        $row->toSearchResult(),
                        ['route' => $source['route']],
                    ))
                    ->all();
            } catch (QueryException $e) {
                // A misconfigured $searchableFields (column doesn't exist) should not 500 the palette.
                // Skip this source and log so a developer can fix it.
                Log::warning('Master Search: skipping source due to query error', [
                    'module' => $source['module'],
                    'model' => $modelClass,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if (empty($rows)) {
                continue;
            }

            $results[] = [
                'module' => $source['module'],
                'label' => $source['label'],
                'icon' => $source['icon'],
                'route' => $source['route'],
                'count' => count($rows),
                'rows' => $rows,
            ];
        }

        return $results;
    }
}
