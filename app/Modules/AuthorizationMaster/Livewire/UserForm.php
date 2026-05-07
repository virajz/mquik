<?php

namespace App\Modules\AuthorizationMaster\Livewire;

use App\Models\User;
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

    public string $password = '';

    /** @var array<int, int> */
    public array $selectedRoles = [];

    public ?string $createdPassword = null;

    public bool $createdNotice = false;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
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

    public function regeneratePassword(): void
    {
        $this->password = $this->generatePassword();
    }

    public function save(): void
    {
        $this->authorize('authorization_master.create');

        $data = $this->validate();

        $plainPassword = $data['password'] !== '' ? $data['password'] : $this->generatePassword();

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($plainPassword),
        ]);
        // Admin-created users skip the email verification round-trip. `email_verified_at`
        // isn't in the User #[Fillable] list, so set it explicitly.
        $user->forceFill(['email_verified_at' => now()])->save();

        if (! empty($data['selectedRoles'])) {
            $roleNames = Role::query()->whereIn('id', $data['selectedRoles'])->pluck('name')->all();
            $user->syncRoles($roleNames);
        }

        $this->createdPassword = $plainPassword;
        $this->createdNotice = true;

        Flux::toast(
            text: 'User '.$user->name.' created. Share the temporary password securely.',
            variant: 'success',
        );

        // Don't dispatch the parent-refresh event yet — that re-renders Users.php and would
        // reset this component's state, hiding the password before the admin can copy it.
        // Defer the refresh to dismissNotice() which fires when the admin clicks Done.
    }

    public function dismissNotice(): void
    {
        $this->createdNotice = false;
        $this->createdPassword = null;
        $this->resetForm();
        $this->dispatch('authorization-master:user-created'); // refresh Users table now
        Flux::modal('authorization-master-user-form')->close();
    }

    protected function resetForm(): void
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->selectedRoles = [];
        $this->createdPassword = null;
        $this->createdNotice = false;
    }

    protected function generatePassword(): string
    {
        // 12 chars, mixed case + digits + a symbol — easy to read aloud, hard to guess.
        return Str::password(length: 12, letters: true, numbers: true, symbols: false);
    }

    public function render()
    {
        return view('authorization-master::user-form', [
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
