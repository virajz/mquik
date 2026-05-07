<?php

namespace App\Modules\LocationMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\LocationMaster\Models\LocationMaster;
use App\Modules\RegionMaster\Models\RegionMaster;
use Illuminate\Support\Facades\Validator;

class LocationImporter implements Importable
{
    public function label(): string
    {
        return 'Locations';
    }

    public function columns(): array
    {
        return [
            'code' => ['label' => 'Code', 'required' => true, 'type' => 'string'],
            'name' => ['label' => 'Name', 'required' => true, 'type' => 'string'],
            'is_head_office' => ['label' => 'Head Office', 'required' => false, 'type' => 'boolean', 'default' => false, 'help' => 'YES/NO, true/false, or 1/0.'],
            'address' => ['label' => 'Address', 'required' => false, 'type' => 'string'],
            'city' => ['label' => 'City', 'required' => false, 'type' => 'string', 'help' => 'Must match an existing City region (case-insensitive).'],
            'state' => ['label' => 'State', 'required' => false, 'type' => 'string', 'help' => 'Must match an existing State region (case-insensitive).'],
            'pincode' => ['label' => 'Pincode', 'required' => false, 'type' => 'string'],
            'phone' => ['label' => 'Phone', 'required' => false, 'type' => 'string'],
            'email' => ['label' => 'Email', 'required' => false, 'type' => 'string'],
            'gstin' => ['label' => 'GSTIN', 'required' => false, 'type' => 'string'],
            'is_active' => ['label' => 'Active', 'required' => false, 'type' => 'boolean', 'default' => true],
            'notes' => ['label' => 'Notes', 'required' => false, 'type' => 'string'],
        ];
    }

    public function uniqueBy(): array
    {
        return ['code'];
    }

    public function validateRow(array $data): array
    {
        $errors = Validator::make($data, [
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'is_head_office' => ['nullable', 'boolean'],
            'address' => ['nullable', 'string', 'max:1000'],
            'pincode' => ['nullable', 'string', 'size:6'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'gstin' => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->errors()->all();

        if (! empty($data['city']) && ! $this->resolveRegionId($data['city'], 'city')) {
            $errors[] = 'City "'.$data['city'].'" does not exist.';
        }
        if (! empty($data['state']) && ! $this->resolveRegionId($data['state'], 'state')) {
            $errors[] = 'State "'.$data['state'].'" does not exist.';
        }

        return $errors;
    }

    public function createRecord(array $data): void
    {
        LocationMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var LocationMaster $existing */
        $existing->update($this->normalize($data));
    }

    protected function normalize(array $data): array
    {
        $skip = ['city', 'state', 'phone', 'email', 'pincode', 'is_head_office', 'is_active'];
        foreach ($data as $k => $v) {
            if (is_string($v) && ! in_array($k, $skip, true)) {
                $data[$k] = strtoupper($v);
            }
        }
        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }
        if (! array_key_exists('is_head_office', $data) || $data['is_head_office'] === null) {
            $data['is_head_office'] = false;
        }

        $data['city_id'] = $this->resolveRegionId($data['city'] ?? null, 'city');
        $data['state_id'] = $this->resolveRegionId($data['state'] ?? null, 'state');
        unset($data['city'], $data['state']);

        return $data;
    }

    protected function resolveRegionId(?string $name, string $kind): ?int
    {
        if (! $name) {
            return null;
        }

        return RegionMaster::query()
            ->where('kind', $kind)
            ->whereLike('name', $name, caseSensitive: false)
            ->where('is_active', true)
            ->value('id');
    }
}
