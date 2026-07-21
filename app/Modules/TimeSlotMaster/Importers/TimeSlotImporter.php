<?php

namespace App\Modules\TimeSlotMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\TimeSlotMaster\Models\TimeSlotMaster;
use Illuminate\Support\Facades\Validator;

class TimeSlotImporter implements Importable
{
    public function label(): string
    {
        return 'Time Slots';
    }

    public function columns(): array
    {
        return [
            'name' => [
                'label' => 'Name',
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
            'slot_start_time' => [
                'label' => 'Start Time',
                'required' => true,
                'type' => 'string',
                'help' => '24-hour HH:MM, e.g. 09:00.',
            ],
            'slot_end_time' => [
                'label' => 'End Time',
                'required' => true,
                'type' => 'string',
                'help' => '24-hour HH:MM, e.g. 10:00.',
            ],
            'max_vehicles_per_slot' => [
                'label' => 'Max Vehicles',
                'required' => false,
                'type' => 'integer',
                'default' => 5,
                'help' => 'Vehicles the workshop can take in this window.',
            ],
            'buffer_minutes' => [
                'label' => 'Buffer (min)',
                'required' => false,
                'type' => 'integer',
                'default' => 0,
                'help' => 'Optional changeover gap after the slot.',
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
            'slot_start_time' => ['required', 'date_format:H:i'],
            'slot_end_time' => ['required', 'date_format:H:i', 'after:slot_start_time'],
            'max_vehicles_per_slot' => ['nullable', 'integer', 'min:1', 'max:999'],
            'buffer_minutes' => ['nullable', 'integer', 'min:0', 'max:480'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return $validator->errors()->all();
    }

    public function createRecord(array $data): void
    {
        TimeSlotMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var TimeSlotMaster $existing */
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
