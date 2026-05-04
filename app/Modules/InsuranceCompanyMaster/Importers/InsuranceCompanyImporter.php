<?php

namespace App\Modules\InsuranceCompanyMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use Illuminate\Support\Facades\Validator;

class InsuranceCompanyImporter implements Importable
{
    public function label(): string
    {
        return 'Insurance Companies';
    }

    public function columns(): array
    {
        return [
            'name' => ['label' => 'Company Name', 'required' => true, 'type' => 'string'],
            'short_name' => ['label' => 'Short Name', 'required' => false, 'type' => 'string'],
            'gstin' => ['label' => 'GSTIN', 'required' => false, 'type' => 'string', 'help' => '15 chars, e.g. 22AAAAA0000A1Z5'],
            'contact_person' => ['label' => 'Contact Person', 'required' => false, 'type' => 'string'],
            'phone' => ['label' => 'Phone', 'required' => false, 'type' => 'string'],
            'email' => ['label' => 'Email', 'required' => false, 'type' => 'string'],
            'default_pass_percent' => ['label' => 'Default Pass %', 'required' => false, 'type' => 'decimal', 'default' => 100],
            'address' => ['label' => 'Address', 'required' => false, 'type' => 'string'],
            'notes' => ['label' => 'Notes', 'required' => false, 'type' => 'string'],
            'is_active' => ['label' => 'Active', 'required' => false, 'type' => 'boolean', 'default' => true],
        ];
    }

    public function uniqueBy(): array
    {
        return ['name'];
    }

    public function validateRow(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:20'],
            'gstin' => ['nullable', 'string', 'size:15'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'default_pass_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return $validator->errors()->all();
    }

    public function createRecord(array $data): void
    {
        InsuranceCompanyMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var InsuranceCompanyMaster $existing */
        $existing->update($this->normalize($data));
    }

    protected function normalize(array $data): array
    {
        $skipUppercase = ['email', 'default_pass_percent', 'is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skipUppercase, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }
        if (! array_key_exists('default_pass_percent', $data) || $data['default_pass_percent'] === null) {
            $data['default_pass_percent'] = 100;
        }

        return $data;
    }
}
