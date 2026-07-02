<?php

namespace App\Modules\ChequeBounceReasonMaster\Importers;

use App\Modules\ChequeBounceReasonMaster\Models\ChequeBounceReasonMaster;
use App\Modules\ImportExport\Contracts\Importable;
use Illuminate\Support\Facades\Validator;

class ChequeBounceReasonImporter implements Importable
{
    public function label(): string
    {
        return 'Cheque Bounce Reasons';
    }

    public function columns(): array
    {
        return [
            'name' => [
                'label' => 'Type Name',
                'required' => true,
                'type' => 'string',
                'help' => 'Will be uppercased.',
            ],
            'code' => [
                'label' => 'Code',
                'required' => false,
                'type' => 'string',
                'help' => 'Short code (max 20 chars).',
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
        return ['name'];
    }

    public function validateRow(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return $validator->errors()->all();
    }

    public function createRecord(array $data): void
    {
        ChequeBounceReasonMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var ChequeBounceReasonMaster $existing */
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
