<?php

namespace App\Modules\ServiceTypeMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Support\Facades\Validator;

class ServiceTypeImporter implements Importable
{
    public function label(): string
    {
        return 'Service Types';
    }

    public function columns(): array
    {
        return [
            'name' => ['label' => 'Service Type', 'required' => true, 'type' => 'string'],
            'code' => ['label' => 'Code', 'required' => false, 'type' => 'string', 'help' => 'Short code (max 20).'],
            'workshop_department' => ['label' => 'Workshop Department', 'required' => true, 'type' => 'string', 'help' => 'Must match an existing Workshop Department by name (case-insensitive).'],
            'requires_advisor' => ['label' => 'Requires Advisor', 'required' => false, 'type' => 'boolean', 'default' => true],
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
            'workshop_department' => ['required', 'string'],
            'requires_advisor' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->errors()->all();

        if (! empty($data['workshop_department']) && ! $this->resolveDepartmentId($data['workshop_department'])) {
            $errors[] = 'Workshop Department "'.$data['workshop_department'].'" does not exist.';
        }

        return $errors;
    }

    public function createRecord(array $data): void
    {
        ServiceTypeMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var ServiceTypeMaster $existing */
        $existing->update($this->normalize($data));
    }

    protected function normalize(array $data): array
    {
        $skip = ['workshop_department', 'requires_advisor', 'is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }
        if (! array_key_exists('requires_advisor', $data) || $data['requires_advisor'] === null) {
            $data['requires_advisor'] = true;
        }
        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        $data['workshop_department_id'] = $this->resolveDepartmentId($data['workshop_department'] ?? null);
        unset($data['workshop_department']);

        return $data;
    }

    protected function resolveDepartmentId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        return WorkshopDepartmentMaster::query()
            ->whereLike('name', $name, caseSensitive: false)
            ->where('is_active', true)
            ->value('id');
    }
}
