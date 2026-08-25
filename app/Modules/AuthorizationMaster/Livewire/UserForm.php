<?php

namespace App\Modules\AuthorizationMaster\Livewire;

use App\Models\User;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use App\Support\Otp\OtpService;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserForm extends Component
{
    public const TYPE_EMPLOYEE = 'employee';

    public const TYPE_CONTRACTOR = 'contractor';

    public const TYPE_MANUAL = 'manual';

    /** The vendor type a service contractor is filed under. */
    public const CONTRACTOR_VENDOR_TYPE = 'SERVICE CONTRACTOR';

    public string $userType = self::TYPE_EMPLOYEE;

    public ?int $employeeId = null;

    public ?int $vendorId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    /** @var array<int, int> */
    public array $selectedRoles = [];

    public ?string $mockCode = null;

    public bool $createdNotice = false;

    public ?string $createdFor = null;

    /** @return array<string, string> */
    public static function userTypes(): array
    {
        return [
            self::TYPE_EMPLOYEE => 'Employee',
            self::TYPE_CONTRACTOR => 'Service Contractor',
            self::TYPE_MANUAL => 'Other (enter manually)',
        ];
    }

    protected function rules(): array
    {
        return [
            'userType' => ['required', Rule::in(array_keys(self::userTypes()))],
            'employeeId' => [
                Rule::requiredIf(fn () => $this->userType === self::TYPE_EMPLOYEE),
                'nullable', 'integer',
                // One login per person.
                Rule::unique('users', 'employee_id'),
            ],
            'vendorId' => [
                Rule::requiredIf(fn () => $this->userType === self::TYPE_CONTRACTOR),
                'nullable', 'integer',
                Rule::unique('users', 'vendor_id'),
            ],
            'name' => ['required', 'string', 'max:255'],
            // One reachable identifier is enough — either receives the login OTP.
            'email' => ['required_without:phone', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required_without:email', 'nullable', 'string', 'min:10', 'max:20', Rule::unique('users', 'phone')],
            'selectedRoles' => ['array'],
            'selectedRoles.*' => ['integer', Rule::exists('roles', 'id')],
        ];
    }

    #[On('authorization-master:create-user')]
    public function load(): void
    {
        $this->resetForm();
        $this->resetErrorBag();
    }

    /** Switching type discards whoever was picked under the previous one. */
    public function updatedUserType(): void
    {
        $this->employeeId = null;
        $this->vendorId = null;
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->resetErrorBag();
    }

    /** Picking a person fills their details — the master already has them. */
    public function updatedEmployeeId($value): void
    {
        $this->fillFrom(EmployeeMaster::find($value));
    }

    public function updatedVendorId($value): void
    {
        $this->fillFrom(VendorMaster::find($value));
    }

    protected function fillFrom(mixed $record): void
    {
        if (! $record) {
            return;
        }

        $this->name = (string) $record->name;
        $this->email = (string) ($record->email ?? '');
        $this->phone = preg_replace('/\D/', '', (string) ($record->phone ?? ''));
    }

    /** Employees without a login yet. */
    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()
            ->where('is_active', true)
            ->whereNotIn('id', User::whereNotNull('employee_id')->pluck('employee_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email']);
    }

    /** Vendors filed as service contractors, without a login yet. */
    #[Computed]
    public function contractors()
    {
        $typeId = VendorTypeMaster::whereRaw('upper(name) = ?', [self::CONTRACTOR_VENDOR_TYPE])->value('id');

        if (! $typeId) {
            return collect();
        }

        // Vendor types are a pivot — a vendor can be a contractor and a supplier.
        return VendorMaster::query()
            ->where('is_active', true)
            ->whereHas('vendorTypes', fn ($q) => $q->where('vendor_types.id', $typeId))
            ->whereNotIn('id', User::whereNotNull('vendor_id')->pluck('vendor_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email']);
    }

    public function save(OtpService $otp): void
    {
        $this->authorize('authorization_master.create');

        $data = $this->validate();

        $user = User::create([
            'name' => $data['name'],
            'email' => filled($data['email'] ?? null) ? mb_strtolower($data['email']) : null,
            'phone' => filled($data['phone'] ?? null) ? preg_replace('/\D/', '', $data['phone']) : null,
            'user_type' => $data['userType'],
            'employee_id' => $data['userType'] === self::TYPE_EMPLOYEE ? $data['employeeId'] : null,
            'vendor_id' => $data['userType'] === self::TYPE_CONTRACTOR ? $data['vendorId'] : null,
            // A throwaway nobody sees: they set their own via the code below.
            'password' => Hash::make(Str::password(32)),
            'must_reset_password' => true,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        if (! empty($data['selectedRoles'])) {
            $user->syncRoles(Role::query()->whereIn('id', $data['selectedRoles'])->pluck('name')->all());
        }

        $this->mockCode = $otp->issue($user);
        $this->createdFor = $user->phone;
        $this->createdNotice = true;

        Flux::toast(text: 'User created. They have been sent a code to set their password.', variant: 'success');
    }

    public function dismissNotice(): void
    {
        $this->createdNotice = false;
        $this->mockCode = null;
        $this->createdFor = null;
        $this->resetForm();
        $this->dispatch('authorization-master:user-created');
        Flux::modal('authorization-master-user-form')->close();
    }

    /** A person added from the quick-add modals lands straight in the picker. */
    #[On('authorization-master:person-added')]
    public function personAdded(string $type, int $id): void
    {
        unset($this->employees, $this->contractors);

        if ($type === self::TYPE_EMPLOYEE) {
            $this->userType = self::TYPE_EMPLOYEE;
            $this->employeeId = $id;
            $this->updatedEmployeeId($id);

            return;
        }

        $this->userType = self::TYPE_CONTRACTOR;
        $this->vendorId = $id;
        $this->updatedVendorId($id);
    }

    protected function resetForm(): void
    {
        $this->userType = self::TYPE_EMPLOYEE;
        $this->employeeId = null;
        $this->vendorId = null;
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->selectedRoles = [];
        $this->mockCode = null;
        $this->createdFor = null;
        $this->createdNotice = false;
    }

    public function render()
    {
        return view('authorization-master::user-form', [
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
