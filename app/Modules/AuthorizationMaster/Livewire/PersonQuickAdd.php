<?php

namespace App\Modules\AuthorizationMaster\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Add the person, then carry on making their login.
 *
 * Creating a user for someone who is not yet in the employee or vendor master
 * otherwise means abandoning the form, going to another screen, and starting
 * again. This captures the few fields a login needs and hands the new id back
 * to the picker.
 */
class PersonQuickAdd extends Component
{
    public string $type = UserForm::TYPE_EMPLOYEE;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    protected function rules(): array
    {
        $table = $this->type === UserForm::TYPE_EMPLOYEE ? 'employees' : 'vendors';

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique($table, 'email')],
        ];
    }

    #[On('authorization-master:quick-add-person')]
    public function open(string $type): void
    {
        $this->reset(['name', 'phone', 'email']);
        $this->resetErrorBag();
        $this->type = $type;

        Flux::modal('authorization-master-person-quick-add')->show();
    }

    public function save(): void
    {
        $data = $this->validate();

        $payload = [
            'name' => mb_strtoupper(trim($data['name'])),
            'phone' => preg_replace('/\D/', '', $data['phone']),
            'email' => $data['email'] ? mb_strtolower($data['email']) : null,
            'is_active' => true,
        ];

        if ($this->type === UserForm::TYPE_EMPLOYEE) {
            $this->authorize('employee_master.create');

            // employee_code and joining_date are required with no generator on
            // the model; joining today is the sane default for a quick add.
            $record = EmployeeMaster::create($payload + [
                'employee_code' => $this->nextCode(EmployeeMaster::class, 'employee_code', 'EMP-'),
                'joining_date' => now()->toDateString(),
            ]);
        } else {
            $this->authorize('vendor_master.create');

            $type = VendorTypeMaster::whereRaw('upper(name) = ?', [UserForm::CONTRACTOR_VENDOR_TYPE])->first()
                ?? VendorTypeMaster::create(['name' => UserForm::CONTRACTOR_VENDOR_TYPE, 'is_active' => true]);

            $record = VendorMaster::create($payload + [
                'vendor_code' => $this->nextCode(VendorMaster::class, 'vendor_code', 'VND-'),
            ]);

            // A vendor can hold several types — it is a pivot, not a column.
            $record->vendorTypes()->syncWithoutDetaching([$type->id]);
        }

        $this->dispatch('authorization-master:person-added', type: $this->type, id: $record->id);

        Flux::modal('authorization-master-person-quick-add')->close();
        Flux::toast(text: $record->name.' added.', variant: 'success');
    }

    /**
     * Next code in sequence for a master that requires one.
     *
     * @param  class-string<Model>  $model
     */
    protected function nextCode(string $model, string $column, string $prefix): string
    {
        $last = $model::query()
            ->where($column, 'like', $prefix.'%')
            ->orderByRaw('length('.$column.') desc')
            ->orderByDesc($column)
            ->value($column);

        $next = ((int) preg_replace('/\D/', '', (string) $last)) + 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        return view('authorization-master::person-quick-add');
    }
}
