<?php

namespace App\Modules\SpareMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Support\Facades\Validator;

class SpareImporter implements Importable
{
    public function label(): string
    {
        return 'Spares';
    }

    public function columns(): array
    {
        return [
            'name' => ['label' => 'Spare Name', 'required' => true, 'type' => 'string', 'help' => 'Will be uppercased.'],
            'spare_code' => ['label' => 'Part No.', 'required' => false, 'type' => 'string', 'help' => 'Unique part number; uppercased.'],
            'description' => ['label' => 'Description', 'required' => false, 'type' => 'string'],
            'hsn_code' => ['label' => 'HSN Code', 'required' => false, 'type' => 'string'],
            'brand_name' => ['label' => 'Brand', 'required' => false, 'type' => 'string', 'help' => 'Looked up by name; created if missing.'],
            'tax_name' => ['label' => 'Tax', 'required' => false, 'type' => 'string', 'help' => 'Tax slab name (must already exist).'],
            'rate_before_tax' => ['label' => 'Rate Before Tax', 'required' => false, 'type' => 'numeric'],
            'inventory_group_name' => ['label' => 'Inventory Group', 'required' => false, 'type' => 'string', 'help' => 'Top-level group name; created if missing.'],
            'inventory_sub_group_name' => ['label' => 'Sub Group', 'required' => false, 'type' => 'string', 'help' => 'Sub-group within the parent group.'],
            'department_name' => ['label' => 'Department', 'required' => false, 'type' => 'string', 'help' => 'Workshop department name (must already exist).'],
            'uom_name' => ['label' => 'UoM', 'required' => false, 'type' => 'string', 'help' => 'Unit of measure name; created if missing.'],
            'min_qty' => ['label' => 'Min Qty', 'required' => false, 'type' => 'numeric'],
            'max_qty' => ['label' => 'Max Qty', 'required' => false, 'type' => 'numeric'],
            'barcode_type' => ['label' => 'Barcode Type', 'required' => false, 'type' => 'string', 'help' => 'EAN-13, CODE-128, or QR.'],
            'mrp' => ['label' => 'MRP', 'required' => false, 'type' => 'decimal', 'help' => 'Printed price, tax inclusive.'],
            'location' => ['label' => 'Godown', 'required' => false, 'type' => 'string'],
            'spare_type' => ['label' => 'Part Type', 'required' => false, 'type' => 'string', 'default' => 'vehicle_specific', 'help' => 'vehicle_specific, tyre or common.'],
            'tyre_dimension' => ['label' => 'Tyre Dimension', 'required' => false, 'type' => 'string'],
            'rim_size' => ['label' => 'Rim Size', 'required' => false, 'type' => 'string'],
            'load_speed_index' => ['label' => 'LI-SI', 'required' => false, 'type' => 'string'],
            'tread_pattern' => ['label' => 'Tread Pattern', 'required' => false, 'type' => 'string'],
            'remark' => ['label' => 'Remark', 'required' => false, 'type' => 'string'],
            'is_active' => ['label' => 'Active', 'required' => false, 'type' => 'boolean', 'default' => true, 'help' => 'YES/NO.'],
        ];
    }

    public function uniqueBy(): array
    {
        return ['spare_code'];
    }

    public function validateRow(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'spare_code' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:1000'],
            'hsn_code' => ['nullable', 'string', 'max:16'],
            'rate_before_tax' => ['nullable', 'numeric', 'min:0'],
            'min_qty' => ['nullable', 'numeric', 'min:0'],
            'max_qty' => ['nullable', 'numeric', 'min:0'],
            'barcode_type' => ['nullable', 'string', 'in:EAN-13,CODE-128,QR'],
            'mrp' => ['nullable', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:64'],
            'spare_type' => ['nullable', 'string', 'in:vehicle_specific,tyre,common'],
            'tyre_dimension' => ['nullable', 'string', 'max:32'],
            'rim_size' => ['nullable', 'string', 'max:16'],
            'load_speed_index' => ['nullable', 'string', 'max:16'],
            'tread_pattern' => ['nullable', 'string', 'max:32'],
            'remark' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return $validator->errors()->all();
    }

    public function createRecord(array $data): void
    {
        SpareMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var SpareMaster $existing */
        $existing->update($this->normalize($data));
    }

    /**
     * Resolve named lookups to FK ids, uppercase strings, default booleans.
     */
    protected function normalize(array $data): array
    {
        foreach (['name', 'spare_code', 'description', 'hsn_code', 'barcode_type', 'location', 'tyre_dimension', 'rim_size', 'load_speed_index', 'tread_pattern', 'remark'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $brandName = $this->pull($data, 'brand_name');
        if ($brandName !== null) {
            $data['spare_brand_id'] = SpareBrandMaster::firstOrCreate(['name' => $brandName], ['is_active' => true])->id;
        }

        $taxName = $this->pull($data, 'tax_name');
        if ($taxName !== null) {
            $data['tax_id'] = TaxMaster::query()->whereLike('name', $taxName, caseSensitive: false)->value('id');
        }

        $deptName = $this->pull($data, 'department_name');
        if ($deptName !== null) {
            $data['workshop_department_id'] = WorkshopDepartmentMaster::query()->whereLike('name', $deptName, caseSensitive: false)->value('id');
        }

        $uomName = $this->pull($data, 'uom_name');
        if ($uomName !== null) {
            $data['uom_id'] = UnitOfMeasureMaster::firstOrCreate(['name' => $uomName], ['is_active' => true])->id;
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
        if (! array_key_exists('spare_type', $data) || $data['spare_type'] === null || $data['spare_type'] === '') {
            $data['spare_type'] = SpareMaster::TYPE_VEHICLE_SPECIFIC;
        }

        return $data;
    }

    /**
     * Take a key out of the array and uppercase if string. Returns null if missing or blank.
     */
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
