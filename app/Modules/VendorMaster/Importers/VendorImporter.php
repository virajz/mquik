<?php

namespace App\Modules\VendorMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use Illuminate\Support\Facades\Validator;

class VendorImporter implements Importable
{
    public function label(): string
    {
        return 'Vendors';
    }

    public function columns(): array
    {
        return [
            'vendor_code' => ['label' => 'Code', 'required' => true, 'type' => 'string'],
            'name' => ['label' => 'Name', 'required' => true, 'type' => 'string'],
            'vendor_type' => ['label' => 'Vendor Type', 'required' => true, 'type' => 'string', 'help' => 'Must match an existing Vendor Type by name (case-insensitive).'],
            'phone' => ['label' => 'Phone', 'required' => true, 'type' => 'string'],
            'alternate_phone' => ['label' => 'Alt Phone', 'required' => false, 'type' => 'string'],
            'email' => ['label' => 'Email', 'required' => false, 'type' => 'string'],
            'address' => ['label' => 'Address', 'required' => false, 'type' => 'string'],
            'city' => ['label' => 'City', 'required' => false, 'type' => 'string'],
            'state' => ['label' => 'State', 'required' => false, 'type' => 'string'],
            'pincode' => ['label' => 'Pincode', 'required' => false, 'type' => 'string'],
            'pan' => ['label' => 'PAN', 'required' => false, 'type' => 'string'],
            'gstin' => ['label' => 'GSTIN', 'required' => false, 'type' => 'string'],
            'bank_name' => ['label' => 'Bank', 'required' => false, 'type' => 'string'],
            'bank_branch' => ['label' => 'Branch', 'required' => false, 'type' => 'string'],
            'ifsc' => ['label' => 'IFSC', 'required' => false, 'type' => 'string'],
            'account_no' => ['label' => 'Account No', 'required' => false, 'type' => 'string'],
            'account_holder' => ['label' => 'Account Holder', 'required' => false, 'type' => 'string'],
            'credit_days' => ['label' => 'Credit Days', 'required' => false, 'type' => 'integer', 'default' => 0],
            'credit_limit' => ['label' => 'Credit Limit', 'required' => false, 'type' => 'number', 'default' => 0],
            'payment_terms' => ['label' => 'Payment Terms', 'required' => false, 'type' => 'string'],
            'is_active' => ['label' => 'Active', 'required' => false, 'type' => 'boolean', 'default' => true],
            'notes' => ['label' => 'Notes', 'required' => false, 'type' => 'string'],
        ];
    }

    public function uniqueBy(): array
    {
        return ['vendor_code'];
    }

    public function validateRow(array $data): array
    {
        $errors = Validator::make($data, [
            'vendor_code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:255'],
            'vendor_type' => ['required', 'string'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'pincode' => ['nullable', 'string', 'size:6'],
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'gstin' => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'ifsc' => ['nullable', 'string', 'size:11'],
            'credit_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ])->errors()->all();

        if (! empty($data['vendor_type']) && ! $this->resolveVendorTypeId($data['vendor_type'])) {
            $errors[] = 'Vendor Type "'.$data['vendor_type'].'" does not exist.';
        }

        return $errors;
    }

    public function createRecord(array $data): void
    {
        VendorMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var VendorMaster $existing */
        $existing->update($this->normalize($data));
    }

    protected function normalize(array $data): array
    {
        $skip = ['email', 'vendor_type', 'phone', 'alternate_phone', 'pincode', 'account_no', 'credit_days', 'credit_limit', 'is_active'];
        foreach ($data as $k => $v) {
            if (is_string($v) && ! in_array($k, $skip, true)) {
                $data[$k] = strtoupper($v);
            }
        }
        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        $data['vendor_type_id'] = $this->resolveVendorTypeId($data['vendor_type'] ?? null);
        unset($data['vendor_type']);

        return $data;
    }

    protected function resolveVendorTypeId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        return VendorTypeMaster::query()
            ->whereLike('name', $name, caseSensitive: false)
            ->where('is_active', true)
            ->value('id');
    }
}
