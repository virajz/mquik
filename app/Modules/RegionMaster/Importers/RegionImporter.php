<?php

namespace App\Modules\RegionMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\RegionMaster\Models\RegionMaster;
use Illuminate\Support\Facades\Validator;

class RegionImporter implements Importable
{
    public function label(): string
    {
        return 'Regions';
    }

    public function columns(): array
    {
        return [
            'kind' => [
                'label' => 'Kind',
                'required' => true,
                'type' => 'string',
                'help' => 'state | city | area | pincode',
            ],
            'name' => [
                'label' => 'Name',
                'required' => true,
                'type' => 'string',
                'help' => 'Will be uppercased.',
            ],
            'code' => [
                'label' => 'Code',
                'required' => false,
                'type' => 'string',
                'help' => 'Optional short code (max 20 chars).',
            ],
            'parent_name' => [
                'label' => 'Parent',
                'required' => false,
                'type' => 'string',
                'help' => 'Parent region name (case-insensitive). Required for city/area/pincode rows.',
            ],
            'is_active' => [
                'label' => 'Active',
                'required' => false,
                'type' => 'boolean',
                'default' => true,
                'help' => 'YES/NO, true/false, or 1/0.',
            ],
            'notes' => [
                'label' => 'Notes',
                'required' => false,
                'type' => 'string',
            ],
        ];
    }

    /**
     * Composite uniqueness sits on (kind, parent_id, name). The engine matches on `name`
     * for upsert routing; in normalize() we resolve parent_id and rely on the DB unique
     * for the safety net.
     */
    public function uniqueBy(): array
    {
        return ['name'];
    }

    public function validateRow(array $data): array
    {
        $errors = [];

        $kind = isset($data['kind']) ? strtolower(trim((string) $data['kind'])) : '';
        $parentKind = $this->parentKindFor($kind);

        $parentName = isset($data['parent_name']) ? trim((string) $data['parent_name']) : '';

        if ($parentKind === null && $parentName !== '') {
            $errors[] = 'A state cannot have a parent.';
        }

        if ($parentKind !== null && $parentName === '') {
            $errors[] = "Kind '{$kind}' requires a parent_name.";
        }

        if ($parentKind !== null && $parentName !== '') {
            $parent = RegionMaster::where('kind', $parentKind)
                ->whereLike('name', strtoupper($parentName), caseSensitive: false)
                ->first();
            if (! $parent) {
                $errors[] = "Parent {$parentKind} '{$parentName}' not found.";
            }
        }

        $fieldErrors = Validator::make($data, [
            'kind' => ['required', 'in:state,city,area,pincode'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->errors()->all();

        return array_merge($errors, $fieldErrors);
    }

    public function createRecord(array $data): void
    {
        RegionMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var RegionMaster $existing */
        $existing->update($this->normalize($data));
    }

    protected function parentKindFor(string $kind): ?string
    {
        return match ($kind) {
            'state' => null,
            'city' => 'state',
            'area' => 'city',
            'pincode' => 'area',
            default => null,
        };
    }

    protected function normalize(array $data): array
    {
        if (isset($data['kind']) && is_string($data['kind'])) {
            $data['kind'] = strtolower(trim($data['kind']));
        }
        if (isset($data['name']) && is_string($data['name'])) {
            $data['name'] = strtoupper($data['name']);
        }
        if (isset($data['code']) && is_string($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        $parentName = isset($data['parent_name']) ? trim((string) $data['parent_name']) : '';
        $parentKind = $this->parentKindFor((string) ($data['kind'] ?? ''));

        if ($parentKind !== null && $parentName !== '') {
            $parent = RegionMaster::where('kind', $parentKind)
                ->whereLike('name', strtoupper($parentName), caseSensitive: false)
                ->first();
            $data['parent_id'] = $parent?->id;
        } else {
            $data['parent_id'] = null;
        }
        unset($data['parent_name']);

        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        return $data;
    }
}
