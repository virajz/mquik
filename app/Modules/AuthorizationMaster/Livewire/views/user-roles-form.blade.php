<div>
    <flux:modal name="authorization-master-user-roles" class="md:w-md">
        <form wire:submit="save" novalidate class="space-y-5">
            <div>
                <flux:heading size="lg">Manage Roles</flux:heading>
                <flux:subheading>
                    @if ($userName !== '')
                        {{ $userName }} &middot; <span class="text-zinc-500">{{ $userEmail }}</span>
                    @else
                        Pick the roles to assign.
                    @endif
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <flux:select
                wire:model="selectedRoles"
                label="Roles"
                multiple
                variant="listbox"
                searchable
                placeholder="Pick one or more roles..."
            >
                @foreach ($roles as $role)
                    <flux:select.option :value="$role->id">{{ $role->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">
                    Save roles
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
