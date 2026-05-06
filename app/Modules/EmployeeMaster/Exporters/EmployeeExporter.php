<?php

namespace App\Modules\EmployeeMaster\Exporters;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class EmployeeExporter implements Exportable
{
    public function label(): string
    {
        return 'Employees';
    }

    public function headers(): array
    {
        return ['ID', 'Code', 'Name', 'Gender', 'DOB', 'Phone', 'Email', 'Designation', 'Department', 'Joining Date', 'Exit Date', 'Aadhar', 'PAN', 'Bank', 'IFSC', 'Account No', 'Active', 'Created At'];
    }

    public function query(): Builder
    {
        return EmployeeMaster::query()->with(['department:id,name', 'designation:id,name'])->orderBy('name');
    }

    /**
     * @param  EmployeeMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id, $model->employee_code, $model->name, $model->gender,
            $model->date_of_birth?->format('Y-m-d'),
            $model->phone, $model->email,
            $model->designation?->name, $model->department?->name,
            $model->joining_date?->format('Y-m-d'),
            $model->exit_date?->format('Y-m-d'),
            $model->aadhar, $model->pan,
            $model->bank_name, $model->ifsc, $model->account_no,
            $model->is_active ? 'YES' : 'NO',
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'employees';
    }
}
