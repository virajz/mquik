<?php

namespace App\Modules\EmployeeMaster\Importers;

use App\Modules\DepartmentMaster\Models\DepartmentMaster;
use App\Modules\DesignationMaster\Models\DesignationMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\ImportExport\Contracts\Importable;
use Illuminate\Support\Facades\Validator;

class EmployeeImporter implements Importable
{
    public function label(): string
    {
        return 'Employees';
    }

    public function columns(): array
    {
        return [
            'employee_code' => ['label' => 'Code', 'required' => true, 'type' => 'string'],
            'name' => ['label' => 'Name', 'required' => true, 'type' => 'string'],
            'gender' => ['label' => 'Gender', 'required' => false, 'type' => 'string', 'help' => 'male | female | other'],
            'date_of_birth' => ['label' => 'DOB', 'required' => false, 'type' => 'date'],
            'phone' => ['label' => 'Phone', 'required' => true, 'type' => 'string'],
            'email' => ['label' => 'Email', 'required' => false, 'type' => 'string'],
            'designation' => ['label' => 'Designation', 'required' => true, 'type' => 'string', 'help' => 'Must match an existing Designation by name (case-insensitive).'],
            'department' => ['label' => 'Department', 'required' => true, 'type' => 'string', 'help' => 'Must match an existing Department by name (case-insensitive).'],
            'joining_date' => ['label' => 'Joining Date', 'required' => true, 'type' => 'date'],
            'exit_date' => ['label' => 'Exit Date', 'required' => false, 'type' => 'date'],
            'aadhar' => ['label' => 'Aadhar', 'required' => false, 'type' => 'string'],
            'pan' => ['label' => 'PAN', 'required' => false, 'type' => 'string'],
            'bank_name' => ['label' => 'Bank', 'required' => false, 'type' => 'string'],
            'ifsc' => ['label' => 'IFSC', 'required' => false, 'type' => 'string'],
            'account_no' => ['label' => 'Account No', 'required' => false, 'type' => 'string'],
            'is_active' => ['label' => 'Active', 'required' => false, 'type' => 'boolean', 'default' => true],
        ];
    }

    public function uniqueBy(): array
    {
        return ['employee_code'];
    }

    public function validateRow(array $data): array
    {
        $errors = Validator::make($data, [
            'employee_code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'email' => ['nullable', 'email'],
            'designation' => ['required', 'string'],
            'department' => ['required', 'string'],
            'joining_date' => ['required', 'date'],
            'exit_date' => ['nullable', 'date'],
            'aadhar' => ['nullable', 'string', 'size:12'],
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'is_active' => ['nullable', 'boolean'],
        ])->errors()->all();

        if (! empty($data['designation']) && ! $this->resolveDesignationId($data['designation'])) {
            $errors[] = 'Designation "'.$data['designation'].'" does not exist.';
        }
        if (! empty($data['department']) && ! $this->resolveDepartmentId($data['department'])) {
            $errors[] = 'Department "'.$data['department'].'" does not exist.';
        }

        return $errors;
    }

    public function createRecord(array $data): void
    {
        EmployeeMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var EmployeeMaster $existing */
        $existing->update($this->normalize($data));
    }

    protected function normalize(array $data): array
    {
        $skip = ['email', 'gender', 'designation', 'department', 'date_of_birth', 'joining_date', 'exit_date', 'is_active', 'phone', 'aadhar', 'account_no'];
        foreach ($data as $k => $v) {
            if (is_string($v) && ! in_array($k, $skip, true)) {
                $data[$k] = strtoupper($v);
            }
        }
        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        $data['designation_id'] = $this->resolveDesignationId($data['designation'] ?? null);
        $data['department_id'] = $this->resolveDepartmentId($data['department'] ?? null);
        unset($data['designation'], $data['department']);

        return $data;
    }

    protected function resolveDesignationId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        return DesignationMaster::query()
            ->whereLike('name', $name, caseSensitive: false)
            ->where('is_active', true)
            ->value('id');
    }

    protected function resolveDepartmentId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        return DepartmentMaster::query()
            ->whereLike('name', $name, caseSensitive: false)
            ->where('is_active', true)
            ->value('id');
    }
}
