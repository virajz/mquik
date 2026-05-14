<?php

namespace App\Modules\LabourMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\VehicleSegmentMaster\Models\VehicleSegmentMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Support\Facades\Validator;

class LabourImporter implements Importable
{
    public function label(): string
    {
        return 'Labour';
    }

    public function columns(): array
    {
        return [
            'name' => ['label' => 'Labour Name', 'required' => true, 'type' => 'string', 'help' => 'Will be uppercased.'],
            'labour_code' => ['label' => 'Code', 'required' => false, 'type' => 'string', 'help' => 'Unique labour code; uppercased.'],
            'description' => ['label' => 'Description', 'required' => false, 'type' => 'string'],
            'hsn_sac_code' => ['label' => 'HSN/SAC', 'required' => false, 'type' => 'string'],
            'segment_name' => ['label' => 'Vehicle Segment', 'required' => false, 'type' => 'string', 'help' => 'Looked up by name; created if missing.'],
            'tax_name' => ['label' => 'Tax', 'required' => false, 'type' => 'string', 'help' => 'Tax slab name (must already exist).'],
            'rate_before_tax' => ['label' => 'Rate Before Tax', 'required' => false, 'type' => 'numeric'],
            'department_name' => ['label' => 'Department', 'required' => false, 'type' => 'string', 'help' => 'Workshop department name.'],
            'inventory_group_name' => ['label' => 'Inventory Group', 'required' => false, 'type' => 'string'],
            'inventory_sub_group_name' => ['label' => 'Sub Group', 'required' => false, 'type' => 'string'],
            'is_osl' => ['label' => 'OSL', 'required' => false, 'type' => 'boolean', 'default' => false, 'help' => 'YES if outside-labour.'],
            'remark' => ['label' => 'Remark', 'required' => false, 'type' => 'string'],
            'is_active' => ['label' => 'Active', 'required' => false, 'type' => 'boolean', 'default' => true],
        ];
    }

    public function uniqueBy(): array
    {
        return ['labour_code'];
    }

    public function validateRow(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'labour_code' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:1000'],
            'hsn_sac_code' => ['nullable', 'string', 'max:16'],
            'rate_before_tax' => ['nullable', 'numeric', 'min:0'],
            'is_osl' => ['nullable', 'boolean'],
            'remark' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return $validator->errors()->all();
    }

    public function createRecord(array $data): void
    {
        LabourMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var LabourMaster $existing */
        $existing->update($this->normalize($data));
    }

    protected function normalize(array $data): array
    {
        foreach (['name', 'labour_code', 'description', 'hsn_sac_code', 'remark'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $segName = $this->pull($data, 'segment_name');
        if ($segName !== null) {
            $data['vehicle_segment_id'] = VehicleSegmentMaster::firstOrCreate(['name' => $segName], ['is_active' => true])->id;
        }

        $taxName = $this->pull($data, 'tax_name');
        if ($taxName !== null) {
            $data['tax_id'] = TaxMaster::query()->whereLike('name', $taxName, caseSensitive: false)->value('id');
        }

        $deptName = $this->pull($data, 'department_name');
        if ($deptName !== null) {
            $data['workshop_department_id'] = WorkshopDepartmentMaster::query()->whereLike('name', $deptName, caseSensitive: false)->value('id');
        }

        $groupName = $this->pull($data, 'inventory_group_name');
        $subGroupName = $this->pull($data, 'inventory_sub_group_name');
        if ($groupName !== null) {
            $parent = InventoryGroupMaster::firstOrCreate(['name' => $groupName, 'parent_id' => null], ['is_active' => true]);
            $data['inventory_group_id'] = $parent->id;

            if ($subGroupName !== null) {
                $sub = InventoryGroupMaster::firstOrCreate(
                    ['name' => $subGroupName, 'parent_id' => $parent->id],
                    ['is_active' => true],
                );
                $data['inventory_sub_group_id'] = $sub->id;
            }
        }

        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }
        if (! array_key_exists('is_osl', $data) || $data['is_osl'] === null) {
            $data['is_osl'] = false;
        }

        return $data;
    }

    protected function pull(array &$data, string $key): ?string
    {
        $value = $data[$key] ?? null;
        unset($data[$key]);
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return strtoupper(trim($value));
    }
}
