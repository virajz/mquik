<?php

namespace App\Modules\AuthorizationMaster\Livewire;

use App\Support\ModuleRegistry;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    /** @var array<int, string> */
    public array $permissions = [];

    public bool $isSuperAdmin = false;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('roles', 'name')->ignore($this->editingId),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }

    #[On('authorization-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $role = Role::with('permissions')->findOrFail($id);
        $this->editingId = $role->id;
        $this->name = $role->name;
        $this->permissions = $role->permissions->pluck('name')->all();
        $this->isSuperAdmin = $role->name === 'Super Admin';
    }

    public function toggleGroup(string $group, bool $checked): void
    {
        $slugs = $this->permissionSlugsForGroup($group);

        if ($checked) {
            $this->permissions = array_values(array_unique(array_merge($this->permissions, $slugs)));
        } else {
            $this->permissions = array_values(array_diff($this->permissions, $slugs));
        }
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $role = Role::findOrFail($this->editingId);
            $role->update(['name' => $data['name']]);
        } else {
            $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        }

        // Super Admin always has every permission. Revert any unticking silently with a warning.
        if ($role->name === 'Super Admin') {
            $role->syncPermissions(Permission::query()->pluck('name')->all());
            Flux::toast(
                text: 'Super Admin always retains every permission — your selection was ignored.',
                variant: 'warning',
            );
        } else {
            $role->syncPermissions($data['permissions'] ?? []);
            Flux::toast(
                text: $this->editingId
                    ? 'Role "'.$role->name.'" updated.'
                    : 'Role "'.$role->name.'" created.',
                variant: 'success',
            );
        }

        $this->dispatch('authorization-master:saved');
        $this->resetForm();
        Flux::modal('authorization-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->permissions = [];
        $this->isSuperAdmin = false;
    }

    /**
     * Group permissions by the manifest 'group' of the module they belong to.
     * Slug shape: `{module_snake}.{action}` — we match the prefix back to a module.
     *
     * @return array<string, array<string, array<int, Permission>>>
     */
    protected function groupedPermissions(): array
    {
        /** @var ModuleRegistry $registry */
        $registry = app(ModuleRegistry::class);

        // Build a lookup: snake_module_name => ['group' => '...', 'label' => '...']
        $modulesBySnake = [];
        foreach ($registry->all() as $name => $manifest) {
            $modulesBySnake[Str::snake($name)] = [
                'name' => $name,
                'label' => $manifest['label'] ?? $name,
                'group' => $manifest['group'] ?? 'General',
            ];
        }

        $grouped = [];

        foreach (Permission::query()->orderBy('name')->get() as $permission) {
            $slug = $permission->name;
            $modulePrefix = Str::beforeLast($slug, '.');
            $action = Str::afterLast($slug, '.');

            $info = $modulesBySnake[$modulePrefix] ?? [
                'name' => $modulePrefix,
                'label' => Str::headline($modulePrefix),
                'group' => 'Other',
            ];

            $group = $info['group'];
            $moduleLabel = $info['label'];

            $grouped[$group] ??= [];
            $grouped[$group][$moduleLabel] ??= [];
            $grouped[$group][$moduleLabel][$action] = $permission;
        }

        ksort($grouped);
        foreach ($grouped as $group => &$modules) {
            ksort($modules);
        }

        return $grouped;
    }

    /**
     * @return array<int, string>
     */
    protected function permissionSlugsForGroup(string $group): array
    {
        $slugs = [];
        foreach ($this->groupedPermissions()[$group] ?? [] as $modulePerms) {
            foreach ($modulePerms as $permission) {
                $slugs[] = $permission->name;
            }
        }

        return $slugs;
    }

    public function render()
    {
        return view('authorization-master::form', [
            'grouped' => $this->groupedPermissions(),
        ]);
    }
}
