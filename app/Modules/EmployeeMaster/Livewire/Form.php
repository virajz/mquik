<?php

namespace App\Modules\EmployeeMaster\Livewire;

use App\Modules\DepartmentMaster\Models\DepartmentMaster;
use App\Modules\DesignationMaster\Models\DesignationMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

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

    public ?string $joining_date = null;

    public ?string $exit_date = null;

    public ?string $bank_name = null;

    public ?string $bank_branch = null;

    public ?string $ifsc = null;

    public ?string $account_no = null;

    public bool $is_active = true;

    public ?string $notes = null;

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

    #[On('employee-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();
        if ($id === null) {
            return;
        }

        $r = EmployeeMaster::findOrFail($id);
        foreach (['employee_code', 'name', 'gender', 'phone', 'alternate_phone', 'email', 'address', 'city', 'pincode', 'aadhar', 'pan', 'bank_name', 'bank_branch', 'ifsc', 'account_no', 'notes'] as $k) {
            $this->{$k} = $r->{$k};
        }
        $this->editingId = $r->id;
        $this->designation_id = $r->designation_id;
        $this->department_id = $r->department_id;
        $this->date_of_birth = $r->date_of_birth?->format('Y-m-d');
        $this->joining_date = $r->joining_date?->format('Y-m-d');
        $this->exit_date = $r->exit_date?->format('Y-m-d');
        $this->is_active = $r->is_active;
    }

    public function save(): void
    {
        $data = $this->validate();

        $skip = ['email', 'gender', 'designation_id', 'department_id', 'date_of_birth', 'joining_date', 'exit_date', 'is_active', 'phone', 'alternate_phone', 'aadhar', 'pincode', 'account_no'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            EmployeeMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Employee #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $r = EmployeeMaster::create($data);
            Flux::toast(text: 'Employee #'.$r->id.' created.', variant: 'success');
        }

        $this->dispatch('employee-master:saved');
        $this->resetForm();
        Flux::modal('employee-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->employee_code = '';
        $this->name = '';
        $this->gender = null;
        $this->date_of_birth = null;
        $this->phone = '';
        $this->alternate_phone = null;
        $this->email = null;
        $this->address = null;
        $this->city = null;
        $this->pincode = null;
        $this->aadhar = null;
        $this->pan = null;
        $this->designation_id = null;
        $this->department_id = null;
        $this->joining_date = null;
        $this->exit_date = null;
        $this->bank_name = null;
        $this->bank_branch = null;
        $this->ifsc = null;
        $this->account_no = null;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('employee-master::form', [
            'designations' => DesignationMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => DepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
