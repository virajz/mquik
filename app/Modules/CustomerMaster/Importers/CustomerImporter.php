<?php

namespace App\Modules\CustomerMaster\Importers;

use App\Modules\BusinessTypeMaster\Models\BusinessTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\RegionMaster\Models\RegionMaster;
use Illuminate\Support\Facades\Validator;

class CustomerImporter implements Importable
{
    public function label(): string
    {
        return 'Customers';
    }

    public function columns(): array
    {
        return [
            'name' => ['label' => 'Name', 'required' => true, 'type' => 'string'],
            'business_type' => ['label' => 'Business Type', 'required' => false, 'type' => 'string', 'help' => 'Walking | Loyal | Corporate | Government — match by name (case-insensitive). Defaults to Walking.'],
            'phone' => ['label' => 'Phone', 'required' => true, 'type' => 'string'],
            'alternate_phone' => ['label' => 'Alternate Phone', 'required' => false, 'type' => 'string'],
            'email' => ['label' => 'Email', 'required' => false, 'type' => 'string'],
            'address_line' => ['label' => 'Address (street/house)', 'required' => false, 'type' => 'string'],
            'pincode' => ['label' => 'Pincode', 'required' => false, 'type' => 'string', 'help' => '6 digits — looked up in regions to attach the primary address.'],
            'city' => ['label' => 'City', 'required' => false, 'type' => 'string', 'help' => 'Used only if no matching pincode region exists.'],
            'address_label' => ['label' => 'Address Label', 'required' => false, 'type' => 'string', 'help' => 'Optional label for the imported address (e.g. "Home").'],
            'aadhar' => ['label' => 'Aadhar', 'required' => false, 'type' => 'string', 'help' => '12 digits, no spaces'],
            'pan' => ['label' => 'PAN', 'required' => false, 'type' => 'string', 'help' => '10 chars, e.g. ABCDE1234F'],
            'date_of_birth' => ['label' => 'Date of Birth', 'required' => false, 'type' => 'date'],
            'notes' => ['label' => 'Notes', 'required' => false, 'type' => 'string'],
            'is_active' => ['label' => 'Active', 'required' => false, 'type' => 'boolean', 'default' => true],
        ];
    }

    public function uniqueBy(): array
    {
        return ['phone'];
    }

    public function validateRow(array $data): array
    {
        $errors = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'business_type' => ['nullable', 'string'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address_line' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'size:6'],
            'address_label' => ['nullable', 'string', 'max:50'],
            'aadhar' => ['nullable', 'string', 'size:12'],
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'date_of_birth' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ])->errors()->all();

        if (! empty($data['business_type']) && ! $this->resolveBusinessTypeId($data['business_type'])) {
            $errors[] = 'Business Type "'.$data['business_type'].'" does not exist or is inactive.';
        }

        return $errors;
    }

    public function createRecord(array $data): void
    {
        [$customerData, $addressData] = $this->split($data);
        $customer = CustomerMaster::create($customerData);
        $this->syncPrimaryAddress($customer, $addressData);
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var CustomerMaster $existing */
        [$customerData, $addressData] = $this->split($data);
        $existing->update($customerData);
        $this->syncPrimaryAddress($existing, $addressData);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array{address_line: ?string, region_id: ?int, label: ?string}}
     */
    protected function split(array $data): array
    {
        $skipUppercase = ['email', 'business_type', 'date_of_birth', 'is_active', 'phone', 'alternate_phone', 'aadhar', 'pincode'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skipUppercase, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        $data['business_type_id'] = $this->resolveBusinessTypeId($data['business_type'] ?? null)
            ?? BusinessTypeMaster::firstOrCreate(['name' => 'WALKING'], ['is_active' => true])->id;

        $address = [
            'address_line' => $data['address_line'] ?? null,
            'region_id' => $this->resolveRegionId($data['pincode'] ?? null, $data['city'] ?? null),
            'label' => $data['address_label'] ?? null,
        ];

        unset($data['business_type'], $data['address_line'], $data['city'], $data['pincode'], $data['address_label']);

        return [$data, $address];
    }

    /**
     * @param  array{address_line: ?string, region_id: ?int, label: ?string}  $address
     */
    protected function syncPrimaryAddress(CustomerMaster $customer, array $address): void
    {
        // Skip writing if every field is empty — nothing to record.
        if (empty($address['address_line']) && empty($address['region_id']) && empty($address['label'])) {
            return;
        }

        $primary = $customer->addresses()->where('is_primary', true)->first();

        $payload = [
            'label' => $address['label'],
            'address_line' => $address['address_line'],
            'region_id' => $address['region_id'],
            'is_primary' => true,
        ];

        if ($primary) {
            $primary->update($payload);
        } else {
            $customer->addresses()->create($payload);
        }
    }

    protected function resolveBusinessTypeId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        return BusinessTypeMaster::query()
            ->whereLike('name', $name, caseSensitive: false)
            ->where('is_active', true)
            ->value('id');
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
}
