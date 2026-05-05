<?php

namespace App\Modules\CustomerMaster\Importers;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\ImportExport\Contracts\Importable;
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
            'customer_type' => ['label' => 'Type', 'required' => false, 'type' => 'string', 'help' => 'walking | loyal | corporate'],
            'phone' => ['label' => 'Phone', 'required' => true, 'type' => 'string'],
            'alternate_phone' => ['label' => 'Alternate Phone', 'required' => false, 'type' => 'string'],
            'email' => ['label' => 'Email', 'required' => false, 'type' => 'string'],
            'address' => ['label' => 'Address', 'required' => false, 'type' => 'string'],
            'city' => ['label' => 'City', 'required' => false, 'type' => 'string'],
            'pincode' => ['label' => 'Pincode', 'required' => false, 'type' => 'string'],
            'aadhar' => ['label' => 'Aadhar', 'required' => false, 'type' => 'string', 'help' => '12 digits, no spaces'],
            'pan' => ['label' => 'PAN', 'required' => false, 'type' => 'string', 'help' => '10 chars, e.g. ABCDE1234F'],
            'date_of_birth' => ['label' => 'Date of Birth', 'required' => false, 'type' => 'date'],
            'notes' => ['label' => 'Notes', 'required' => false, 'type' => 'string'],
            'is_active' => ['label' => 'Active', 'required' => false, 'type' => 'boolean', 'default' => true],
        ];
    }

    public function uniqueBy(): array
    {
        // Phone is the natural key at the workshop counter — most customers don't have email.
        return ['phone'];
    }

    public function validateRow(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'customer_type' => ['nullable', 'in:walking,loyal,corporate'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'size:6'],
            'aadhar' => ['nullable', 'string', 'size:12'],
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'date_of_birth' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return $validator->errors()->all();
    }

    public function createRecord(array $data): void
    {
        CustomerMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var CustomerMaster $existing */
        $existing->update($this->normalize($data));
    }

    protected function normalize(array $data): array
    {
        $skipUppercase = ['email', 'customer_type', 'date_of_birth', 'is_active', 'phone', 'alternate_phone', 'aadhar', 'pincode'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skipUppercase, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        // Normalize customer_type — accept any case from CSV
        if (isset($data['customer_type']) && is_string($data['customer_type'])) {
            $data['customer_type'] = strtolower($data['customer_type']);
        } else {
            $data['customer_type'] ??= 'walking';
        }

        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        return $data;
    }
}
