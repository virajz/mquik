<?php

namespace App\Modules\JobDescriptionMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JobDescriptionImporter implements Importable
{
    public function label(): string
    {
        return 'Job Descriptions';
    }

    public function columns(): array
    {
        return [
            'name' => ['label' => 'Job Description', 'required' => true, 'type' => 'string'],
            'code' => ['label' => 'Code', 'required' => false, 'type' => 'string'],
            'category' => ['label' => 'Category', 'required' => true, 'type' => 'string', 'help' => 'frequent | general'],
            'service_type' => ['label' => 'Service Type', 'required' => false, 'type' => 'string', 'help' => 'Match by name (case-insensitive). Optional.'],
            'standard_hours' => ['label' => 'Standard Hours', 'required' => false, 'type' => 'string', 'help' => 'Decimal hours, e.g. 0.50'],
            'is_active' => ['label' => 'Active', 'required' => false, 'type' => 'boolean', 'default' => true],
            'notes' => ['label' => 'Notes', 'required' => false, 'type' => 'string'],
        ];
    }

    public function uniqueBy(): array
    {
        return ['name'];
    }

    public function validateRow(array $data): array
    {
        $errors = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20'],
            'category' => ['required', Rule::in(JobDescriptionMaster::categories())],
            'service_type' => ['nullable', 'string'],
            'standard_hours' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->errors()->all();

        if (! empty($data['service_type']) && ! $this->resolveServiceTypeId($data['service_type'])) {
            $errors[] = 'Service Type "'.$data['service_type'].'" does not exist or is inactive.';
        }

        return $errors;
    }

    public function createRecord(array $data): void
    {
        JobDescriptionMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var JobDescriptionMaster $existing */
        $existing->update($this->normalize($data));
    }

    protected function normalize(array $data): array
    {
        $skip = ['category', 'service_type', 'standard_hours', 'is_active'];
        foreach ($data as $k => $v) {
            if (is_string($v) && ! in_array($k, $skip, true)) {
                $data[$k] = strtoupper($v);
            }
        }
        if (isset($data['category']) && is_string($data['category'])) {
            $data['category'] = strtolower($data['category']);
        } else {
            $data['category'] ??= 'general';
        }
        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        $data['service_type_id'] = $this->resolveServiceTypeId($data['service_type'] ?? null);
        unset($data['service_type']);

        return $data;
    }

    protected function resolveServiceTypeId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        return ServiceTypeMaster::query()
            ->whereLike('name', $name, caseSensitive: false)
            ->where('is_active', true)
            ->value('id');
    }
}
