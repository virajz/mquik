<?php

namespace App\Modules\VehicleModelMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleSegmentMaster\Models\VehicleSegmentMaster;
use Illuminate\Support\Facades\Validator;

class VehicleModelImporter implements Importable
{
    public function label(): string
    {
        return 'Vehicle Models';
    }

    public function columns(): array
    {
        return [
            'brand_name' => ['label' => 'Brand', 'required' => true, 'type' => 'string', 'help' => 'Must match an existing Vehicle Brand name'],
            'name' => ['label' => 'Model', 'required' => true, 'type' => 'string'],
            'segment' => ['label' => 'Segment', 'required' => false, 'type' => 'string', 'help' => 'Match by name (case-insensitive). Optional.'],
            'is_active' => ['label' => 'Active', 'required' => false, 'type' => 'boolean', 'default' => true],
            'notes' => ['label' => 'Notes', 'required' => false, 'type' => 'string'],
        ];
    }

    public function uniqueBy(): array
    {
        return ['brand_id', 'name'];
    }

    public function validateRow(array $data): array
    {
        $brandName = isset($data['brand_name']) ? strtoupper((string) $data['brand_name']) : null;
        $brand = $brandName ? VehicleBrandMaster::where('name', $brandName)->first() : null;

        $errors = [];
        if (! $brand) {
            $errors[] = "Brand '{$brandName}' not found in Vehicle Brands";
        }

        if (! empty($data['segment']) && ! $this->resolveSegmentId($data['segment'])) {
            $errors[] = 'Segment "'.$data['segment'].'" does not exist or is inactive.';
        }

        $fieldErrors = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'segment' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->errors()->all();

        return array_merge($errors, $fieldErrors);
    }

    public function createRecord(array $data): void
    {
        VehicleModelMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        $existing->update($this->normalize($data));
    }

    protected function normalize(array $data): array
    {
        if (isset($data['brand_name']) && is_string($data['brand_name'])) {
            $brand = VehicleBrandMaster::where('name', strtoupper($data['brand_name']))->firstOrFail();
            $data['brand_id'] = $brand->id;
        }
        unset($data['brand_name']);

        if (isset($data['name'])) {
            $data['name'] = strtoupper((string) $data['name']);
        }
        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }

        $data['vehicle_segment_id'] = $this->resolveSegmentId($data['segment'] ?? null);
        unset($data['segment']);

        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        return $data;
    }

    protected function resolveSegmentId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        return VehicleSegmentMaster::query()
            ->whereLike('name', $name, caseSensitive: false)
            ->where('is_active', true)
            ->value('id');
    }
}
