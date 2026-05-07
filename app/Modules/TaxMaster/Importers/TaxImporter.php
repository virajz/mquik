<?php

namespace App\Modules\TaxMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\TaxMaster\Models\TaxMaster;
use Illuminate\Support\Facades\Validator;

class TaxImporter implements Importable
{
    public function label(): string
    {
        return 'Taxes';
    }

    public function columns(): array
    {
        return [
            'name' => [
                'label' => 'Tax Name',
                'required' => true,
                'type' => 'string',
                'help' => 'e.g. GST 18%. Will be uppercased.',
            ],
            'code' => [
                'label' => 'Code',
                'required' => true,
                'type' => 'string',
                'help' => 'Short unique code (max 20 chars), e.g. GST18.',
            ],
            'hsn_sac' => [
                'label' => 'HSN/SAC',
                'required' => false,
                'type' => 'string',
                'help' => '4-8 digit HSN (goods) or SAC (services) code.',
            ],
            'gst_percent' => [
                'label' => 'GST %',
                'required' => true,
                'type' => 'decimal',
                'help' => 'Numeric, 0-50.',
            ],
            'cess_percent' => [
                'label' => 'Cess %',
                'required' => false,
                'type' => 'decimal',
                'default' => '0.00',
                'help' => 'Numeric, 0-200. Defaults to 0.',
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

    public function uniqueBy(): array
    {
        return ['code'];
    }

    public function validateRow(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
            'hsn_sac' => ['nullable', 'string', 'min:4', 'max:8'],
            'gst_percent' => ['required', 'numeric', 'min:0', 'max:50'],
            'cess_percent' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return $validator->errors()->all();
    }

    public function createRecord(array $data): void
    {
        TaxMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var TaxMaster $existing */
        $existing->update($this->normalize($data));
    }

    /** Apply workshop conventions: uppercase strings (skipping numerics/booleans). */
    protected function normalize(array $data): array
    {
        $skip = ['gst_percent', 'cess_percent', 'is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if (! array_key_exists('cess_percent', $data) || $data['cess_percent'] === null || $data['cess_percent'] === '') {
            $data['cess_percent'] = '0.00';
        }
        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        return $data;
    }
}
