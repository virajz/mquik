<?php

namespace App\Modules\AuthorizationMaster\Livewire;

use App\Models\User;
use App\Support\Otp\OtpService;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    /** @var array<int, int> */
    public array $selectedRoles = [];

    /**
     * Shown once after creation only when OTP delivery is mocked — it is the
     * code, not the password. Nobody is ever shown a password.
     */
    public ?string $mockCode = null;

    public bool $createdNotice = false;

    public ?string $createdFor = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            // The number is how the account is verified, so it has to be there
            // and it has to be unique.
            'phone' => ['required', 'string', 'min:10', 'max:20', Rule::unique('users', 'phone')],
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

    public function save(OtpService $otp): void
    {
        $this->authorize('authorization_master.create');

        $data = $this->validate();

        $user = User::create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'phone' => preg_replace('/\D/', '', $data['phone']),
            // A throwaway nobody sees or needs: the user sets their own via the
            // reset flow before they can do anything.
            'password' => Hash::make(Str::password(32)),
            'must_reset_password' => true,
        ]);

        // Admin-created users skip the email verification round-trip.
        $user->forceFill(['email_verified_at' => now()])->save();

        if (! empty($data['selectedRoles'])) {
            $user->syncRoles(Role::query()->whereIn('id', $data['selectedRoles'])->pluck('name')->all());
        }

        // Send them a code to set their own password with.
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

    protected function resetForm(): void
    {
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
