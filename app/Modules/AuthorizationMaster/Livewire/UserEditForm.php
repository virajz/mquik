<?php

namespace App\Modules\AuthorizationMaster\Livewire;

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Edit a login, keeping the master record in step.
 *
 * A login created from an employee or a contractor mirrors that person's name
 * and number. Changing it here without changing the master would leave two
 * versions of the truth, so the edit writes to both — the master is where the
 * rest of the workshop reads the name from.
 */
class UserEditForm extends Component
{
    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public bool $isActive = true;

    /** Where this login came from, so the UI can say what else will change. */
    public ?string $linkedType = null;

    public ?string $linkedName = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->userId)],
            'phone' => ['required', 'string', 'min:10', 'max:20', Rule::unique('users', 'phone')->ignore($this->userId)],
            'isActive' => ['boolean'],
        ];
    }

    #[On('authorization-master:edit-user')]
    public function load(int $id): void
    {
        $this->resetErrorBag();

        $user = User::with(['employee:id,name', 'vendor:id,name'])->findOrFail($id);

        $this->userId = $user->id;
        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->phone = (string) $user->phone;
        $this->isActive = (bool) $user->is_active;

        $this->linkedType = match (true) {
            $user->employee !== null => 'employee',
            $user->vendor !== null => 'contractor',
            default => null,
        };
        $this->linkedName = $user->employee?->name ?? $user->vendor?->name;

        Flux::modal('authorization-master-user-edit')->show();
    }

    public function save(): void
    {
        $this->authorize('authorization_master.update');

        $data = $this->validate();
        $phone = preg_replace('/\D/', '', $data['phone']);

        $user = User::with(['employee', 'vendor'])->findOrFail($this->userId);

        DB::transaction(function () use ($user, $data, $phone) {
            $user->forceFill([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'phone' => $phone,
                'is_active' => $data['isActive'],
            ])->save();

            // Keep the master in step — name and number are the fields that
            // actually drift, and the rest of the app reads them from there.
            $master = $user->employee ?? $user->vendor;

            $master?->forceFill([
                'name' => mb_strtoupper($data['name']),
                'phone' => $phone,
                'email' => mb_strtolower($data['email']),
            ])->save();
        });

        $this->dispatch('authorization-master:user-created');   // the table listens to this

        Flux::modal('authorization-master-user-edit')->close();
        Flux::toast(
            text: $this->linkedName
                ? 'Updated — the '.$this->linkedType.' record was updated too.'
                : 'User updated.',
            variant: 'success',
        );
    }

    public function render()
    {
        return view('authorization-master::user-edit-form');
    }
}
