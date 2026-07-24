<?php

namespace App\Modules\EmployeeMaster\Livewire;

use App\Concerns\HasQuickCreate;
use App\Modules\DepartmentMaster\Models\DepartmentMaster;
use App\Modules\DesignationMaster\Models\DesignationMaster;
use App\Modules\EmployeeCategoryMaster\Models\EmployeeCategoryMaster;
use App\Modules\EmployeeGradeMaster\Models\EmployeeGradeMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Employee')]
class Edit extends Component
{
    use HasQuickCreate;

    public ?int $editingId = null;

    /** Display-only — the code the user typed, shown in the page title. */
    public string $designationSearch = '';

    public string $departmentSearch = '';

    public string $employee_code = '';

    public string $name = '';

    public ?string $gender = null;

    public ?string $date_of_birth = null;

    public string $phone = '';

    public ?string $alternate_phone = null;

    public ?string $email = null;

    public ?string $address = null;

    public ?string $city = null;

    public ?string $pincode = null;

    public ?string $aadhar = null;

    public ?string $pan = null;

    public ?int $designation_id = null;

    public ?int $department_id = null;

    public ?int $employee_category_id = null;

    public ?int $employee_grade_id = null;

    public ?float $ctc = null;

    public ?string $joining_date = null;

    public ?string $exit_date = null;

    public ?string $bank_name = null;

    public ?string $bank_branch = null;

    public ?string $ifsc = null;

    public ?string $account_no = null;

    public bool $is_active = true;

    public ?string $notes = null;

    public function mount(?EmployeeMaster $employeeMaster = null): void
    {
        if ($employeeMaster && $employeeMaster->exists) {
            $this->load($employeeMaster);
        }
    }

    protected function rules(): array
    {
        return [
            'employee_code' => ['required', 'string', 'max:30', Rule::unique('employees', 'employee_code')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'size:6'],
            'aadhar' => ['nullable', 'string', 'size:12', Rule::unique('employees', 'aadhar')->ignore($this->editingId)],
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/', Rule::unique('employees', 'pan')->ignore($this->editingId)],
            'designation_id' => ['required', 'integer', Rule::exists('designations', 'id')->where('is_active', true)],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('is_active', true)],
            'employee_category_id' => ['nullable', 'integer', Rule::exists('employee_categories', 'id')],
            'employee_grade_id' => ['nullable', 'integer', Rule::exists('employee_grades', 'id')],
            'ctc' => ['nullable', 'numeric', 'min:0'],
            'joining_date' => ['required', 'date'],
            'exit_date' => ['nullable', 'date', 'after_or_equal:joining_date'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'ifsc' => ['nullable', 'string', 'size:11'],
            'account_no' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function load(EmployeeMaster $r): void
    {
        foreach (['employee_code', 'name', 'gender', 'phone', 'alternate_phone', 'email', 'address', 'city', 'pincode', 'aadhar', 'pan', 'bank_name', 'bank_branch', 'ifsc', 'account_no', 'notes'] as $k) {
            $this->{$k} = $r->{$k};
        }
        $this->editingId = $r->id;
        $this->designation_id = $r->designation_id;
        $this->department_id = $r->department_id;
        $this->employee_category_id = $r->employee_category_id;
        $this->employee_grade_id = $r->employee_grade_id;
        $this->ctc = $r->ctc === null ? null : (float) $r->ctc;
        $this->date_of_birth = $r->date_of_birth?->format('Y-m-d');
        $this->joining_date = $r->joining_date?->format('Y-m-d');
        $this->exit_date = $r->exit_date?->format('Y-m-d');
        $this->is_active = $r->is_active;
    }

    public function createDesignation(): void
    {
        $this->quickCreate(
            modelClass: DesignationMaster::class,
            targetProperty: 'designation_id',
            searchProperty: 'designationSearch',
            permission: 'designation_master.create',
            label: 'Designation',
        );
    }

    public function createDepartment(): void
    {
        $this->quickCreate(
            modelClass: DepartmentMaster::class,
            targetProperty: 'department_id',
            searchProperty: 'departmentSearch',
            permission: 'department_master.create',
            label: 'Department',
        );
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'employee_master.update' : 'employee_master.create');

        $data = $this->validate();

        $skip = ['email', 'gender', 'designation_id', 'department_id', 'date_of_birth', 'joining_date', 'exit_date', 'is_active', 'phone', 'alternate_phone', 'aadhar', 'pincode', 'account_no'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        $isCreate = $this->editingId === null;

        if ($isCreate) {
            $r = EmployeeMaster::create($data);
            $this->editingId = $r->id;
        } else {
            EmployeeMaster::findOrFail($this->editingId)->update($data);
        }

        Flux::toast(text: 'Employee '.$this->employee_code.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('employee-master.index');
    }

    public function render()
    {
        return view('employee-master::edit', [
            'designations' => DesignationMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => DepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => EmployeeCategoryMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'grades' => EmployeeGradeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
