<?php

namespace App\Modules\ServiceIntervalMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\ServiceIntervalMaster\Models\ServiceIntervalMaster;
use Illuminate\Support\Facades\Validator;

class ServiceIntervalImporter implements Importable
{
    public function label(): string
    {
        return 'Service Intervals';
    }

    public function columns(): array
    {
        return [
            'name' => [
                'label' => 'Service',
                'required' => true,
                'type' => 'string',
                'help' => 'Must match the wording used on job cards, e.g. ENGINE OIL REPLACE. Will be uppercased.',
            ],
            'interval_months' => [
                'label' => 'Every (months)',
                'required' => false,
                'type' => 'integer',
                'help' => '1-120. Leave blank for a service that is not time-based.',
            ],
            'interval_km' => [
                'label' => 'Every (km)',
                'required' => false,
                'type' => 'integer',
                'help' => '100-500000. Leave blank for a service that is not distance-based.',
            ],
            'is_active' => [
                'label' => 'Active',
                'required' => false,
                'type' => 'boolean',
                'default' => true,
                'help' => 'YES/NO, true/false, or 1/0.',
            ],
            'description' => [
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
            'interval_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'interval_km' => ['nullable', 'integer', 'min:100', 'max:500000'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        return $validator->errors()->all();
    }

    public function createRecord(array $data): void
    {
        ServiceIntervalMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var ServiceIntervalMaster $existing */
        $existing->update($this->normalize($data));
    }

    /** Workshop conventions: uppercase text, blanks stay null so "no bound" survives. */
    protected function normalize(array $data): array
    {
        foreach (['name', 'description'] as $key) {
            if (is_string($data[$key] ?? null)) {
                $data[$key] = strtoupper($data[$key]);
            }
        }

        foreach (['interval_months', 'interval_km'] as $key) {
            if (($data[$key] ?? null) === '') {
                $data[$key] = null;
            }
        }

        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        return $data;
    }
}
