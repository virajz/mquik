<?php

namespace App\Modules\PageListMaster\Livewire;

use App\Support\ModuleRegistry;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Page List')]
class Index extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'group')]
    public string $groupFilter = 'all';

    public function updatingSearch(): void
    {
        // No-op: flat list, no pagination.
    }

    public function updatingGroupFilter(): void
    {
        // No-op: flat list, no pagination.
    }

    /**
     * Build the flat row collection from every registered module's manifest + menu.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildRows(ModuleRegistry $registry): Collection
    {
        $rows = collect();

        foreach ($registry->all() as $module) {
            $menuEntries = [];
            $menuFile = ($module['path'] ?? null).'/menu.php';
            if ($module['path'] && file_exists($menuFile)) {
                $menuEntries = (array) require $menuFile;
            }

            // Module with no menu (e.g. ImportExport engine).
            if (empty($menuEntries)) {
                $rows->push([
                    'module' => $module['name'],
                    'label' => $module['label'],
                    'group' => $module['group'],
                    'icon' => $module['icon'],
                    'route' => null,
                    'permission' => null,
                    'order' => 0,
                    'visible' => false,
                    'permissions_declared' => count($module['permissions'] ?? []),
                    'is_searchable' => is_array($module['searchable'] ?? null),
                    'is_exportable' => isset($module['exportable']),
                    'is_importable' => isset($module['importable']),
                ]);

                continue;
            }

            foreach ($menuEntries as $entry) {
                $rows->push([
                    'module' => $module['name'],
                    'label' => $entry['label'] ?? $module['label'],
                    'group' => $entry['group'] ?? $module['group'],
                    'icon' => $entry['icon'] ?? $module['icon'],
                    'route' => $entry['route'] ?? null,
                    'permission' => $entry['permission'] ?? null,
                    'order' => $entry['order'] ?? 0,
                    'visible' => true,
                    'permissions_declared' => count($module['permissions'] ?? []),
                    'is_searchable' => is_array($module['searchable'] ?? null),
                    'is_exportable' => isset($module['exportable']),
                    'is_importable' => isset($module['importable']),
                ]);
            }
        }

        return $rows;
    }

    public function render()
    {
        $registry = app(ModuleRegistry::class);

        $rows = $this->buildRows($registry);

        if ($this->search !== '') {
            $term = strtolower($this->search);
            $rows = $rows->filter(fn (array $r) => str_contains(strtolower((string) $r['label']), $term)
                || str_contains(strtolower((string) $r['module']), $term)
                || str_contains(strtolower((string) ($r['route'] ?? '')), $term));
        }

        if ($this->groupFilter !== 'all') {
            $rows = $rows->where('group', $this->groupFilter);
        }

        $rows = $rows->sortBy([['group', 'asc'], ['order', 'asc']])->values();

        $groups = $registry->all()
            ->pluck('group')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('page-list-master::index', [
            'rows' => $rows,
            'groups' => $groups,
        ]);
    }
}
