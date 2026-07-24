<?php

namespace App\Modules\HsnMaster\Importers;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\ImportExport\Contracts\Importable;
use Illuminate\Support\Facades\Validator;

class HsnImporter implements Importable
{
    public function label(): string
    {
        return 'HSN / SAC Codes';
    }

    public function columns(): array
    {
        return [
            'code' => [
                'label' => 'Code',
                'required' => true,
                'type' => 'string',
                'help' => '4, 6 or 8 digits.',
            ],
            'kind' => [
                'label' => 'Kind',
                'required' => false,
                'type' => 'string',
                'default' => 'hsn',
                'help' => 'hsn (goods) or sac (services).',
            ],
            'gst_percent' => [
                'label' => 'GST %',
                'required' => false,
                'type' => 'decimal',
                'help' => 'Indicative default rate.',
            ],
            'name' => [
                'label' => 'Description',
                'required' => true,
                'type' => 'string',
                'help' => 'Will be uppercased.',
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
            'code' => ['required', 'string', 'regex:/^([0-9]{4}|[0-9]{6}|[0-9]{8})$/'],
            'kind' => ['nullable', 'string', 'in:hsn,sac'],
            'gst_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return $validator->errors()->all();
    }

    public function createRecord(array $data): void
    {
        HsnMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var HsnMaster $existing */
        $existing->update($this->normalize($data));
    }

    /** Apply workshop conventions: uppercase strings, default booleans. */
    protected function normalize(array $data): array
    {
        if (isset($data['name']) && is_string($data['name'])) {
            $data['name'] = strtoupper($data['name']);
        }
        if (isset($data['code']) && is_string($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }
        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        return $data;
    }
}
