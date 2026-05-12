<?php

namespace App\Modules\VendorMaster\Importers;

use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\RegionMaster\Models\RegionMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use Illuminate\Support\Facades\DB;
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
            'vendor_types' => ['label' => 'Vendor Types', 'required' => true, 'type' => 'string', 'help' => 'Semicolon-separated names — must match existing Vendor Types (case-insensitive). e.g. "SPARE PARTS; OSL"'],
            'phone' => ['label' => 'Phone', 'required' => true, 'type' => 'string'],
            'alternate_phone' => ['label' => 'Alt Phone', 'required' => false, 'type' => 'string'],
            'email' => ['label' => 'Email', 'required' => false, 'type' => 'string'],
            'secondary_email' => ['label' => 'Secondary Email', 'required' => false, 'type' => 'string'],
            'address' => ['label' => 'Address', 'required' => false, 'type' => 'string'],
            'pincode' => ['label' => 'Pincode', 'required' => false, 'type' => 'string', 'help' => '6 digits — looked up in regions to attach the vendor location.'],
            'city' => ['label' => 'City', 'required' => false, 'type' => 'string', 'help' => 'Used only if no matching pincode region exists.'],
            'aadhar' => ['label' => 'Aadhar', 'required' => false, 'type' => 'string', 'help' => '12 digits, no spaces'],
            'pan' => ['label' => 'PAN', 'required' => false, 'type' => 'string'],
            'gstin' => ['label' => 'GSTIN', 'required' => false, 'type' => 'string'],
            'bank' => ['label' => 'Bank', 'required' => false, 'type' => 'string', 'help' => 'Bank name — looked up in BankMaster (case-insensitive).'],
            'bank_branch' => ['label' => 'Branch', 'required' => false, 'type' => 'string'],
            'ifsc' => ['label' => 'IFSC', 'required' => false, 'type' => 'string'],
            'account_no' => ['label' => 'Account No', 'required' => false, 'type' => 'string'],
            'account_holder' => ['label' => 'Account Holder', 'required' => false, 'type' => 'string'],
            'credit_days' => ['label' => 'Credit Days', 'required' => false, 'type' => 'integer', 'default' => 0],
            'credit_limit' => ['label' => 'Credit Limit', 'required' => false, 'type' => 'number', 'default' => 0],
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
            'vendor_types' => ['required', 'string'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'secondary_email' => ['nullable', 'email'],
            'pincode' => ['nullable', 'string', 'size:6'],
            'city' => ['nullable', 'string', 'max:255'],
            'bank' => ['nullable', 'string', 'max:255'],
            'aadhar' => ['nullable', 'string', 'size:12'],
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'gstin' => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'ifsc' => ['nullable', 'string', 'size:11'],
            'credit_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ])->errors()->all();

        $missing = $this->unresolvedTypeNames($data['vendor_types'] ?? '');
        if (! empty($missing)) {
            $errors[] = 'Unknown Vendor Type(s): '.implode(', ', $missing);
        }

        return $errors;
    }

    public function createRecord(array $data): void
    {
        [$vendorData, $typeIds] = $this->split($data);
        $vendor = VendorMaster::create($vendorData);
        $vendor->vendorTypes()->sync($typeIds);
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var VendorMaster $existing */
        [$vendorData, $typeIds] = $this->split($data);
        $existing->update($vendorData);
        $existing->vendorTypes()->sync($typeIds);
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<int>}
     */
    protected function split(array $data): array
    {
        $skip = ['email', 'secondary_email', 'vendor_types', 'phone', 'alternate_phone', 'pincode', 'account_no', 'credit_days', 'credit_limit', 'is_active', 'aadhar', 'bank'];
        foreach ($data as $k => $v) {
            if (is_string($v) && ! in_array($k, $skip, true)) {
                $data[$k] = strtoupper($v);
            }
        }
        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        $typeIds = $this->resolveVendorTypeIds($data['vendor_types'] ?? '');
        $data['region_id'] = $this->resolveRegionId($data['pincode'] ?? null, $data['city'] ?? null);
        $data['bank_id'] = $this->resolveBankId($data['bank'] ?? null);

        unset($data['vendor_types'], $data['pincode'], $data['city'], $data['bank']);

        return [$data, $typeIds];
    }

    protected function resolveRegionId(?string $pincode, ?string $city): ?int
    {
        if ($pincode) {
            $id = RegionMaster::query()
                ->where('kind', 'pincode')
                ->where('name', $pincode)
                ->where('is_active', true)
                ->value('id');
            if ($id) {
                return $id;
            }
        }

        if ($city) {
            return RegionMaster::query()
                ->where('kind', 'city')
                ->whereLike('name', $city, caseSensitive: false)
                ->where('is_active', true)
                ->value('id');
        }

        return null;
    }

    protected function resolveBankId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        return BankMaster::query()
            ->whereLike('name', $name, caseSensitive: false)
            ->where('is_active', true)
            ->value('id');
    }

    /**
     * @return list<int>
     */
    protected function resolveVendorTypeIds(string $names): array
    {
        $parts = array_filter(array_map('trim', explode(';', $names)));
        if (empty($parts)) {
            return [];
        }

        return VendorTypeMaster::query()
            ->where('is_active', true)
            ->whereIn(DB::raw('LOWER(name)'), array_map(fn ($n) => mb_strtolower($n), $parts))
            ->pluck('id')
            ->all();
    }

    /**
     * @return list<string>
     */
    protected function unresolvedTypeNames(string $names): array
    {
        $parts = array_filter(array_map('trim', explode(';', $names)));
        if (empty($parts)) {
            return [];
        }

        $found = VendorTypeMaster::query()
            ->where('is_active', true)
            ->whereIn(DB::raw('LOWER(name)'), array_map(fn ($n) => mb_strtolower($n), $parts))
            ->pluck('name')
            ->map(fn ($n) => mb_strtolower($n))
            ->all();

        return array_values(array_filter($parts, fn ($n) => ! in_array(mb_strtolower($n), $found, true)));
    }
}
