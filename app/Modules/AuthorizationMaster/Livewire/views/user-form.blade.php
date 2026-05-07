<div>
    <flux:modal name="authorization-master-user-form" class="md:w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">New User</flux:heading>
                <flux:subheading>
                    Admin-created users skip email verification. Share the temporary password securely; the user can change it from their profile after first login.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            @if ($createdNotice)
                <flux:callout color="lime" icon="check-circle">
                    <flux:callout.heading>User created</flux:callout.heading>
                    <flux:callout.text>
                        Hand the user this <strong>temporary password</strong>. It won't be shown again.
                    </flux:callout.text>
                    <div class="mt-3">
                        <flux:input
                            value="{{ $createdPassword }}"
                            readonly
                            copyable
                            class:input="font-mono tracking-wide"
                        />
                    </div>
                    <x-slot name="actions">
                        <flux:button size="sm" wire:click="dismissNotice">Done</flux:button>
                    </x-slot>
                </flux:callout>
            @else
                <div class="space-y-4">
                    <flux:input
                        wire:model="name"
                        label="Name"
                        placeholder="e.g. Ravi Sharma"
                        required
                        autofocus
                    />

                    <flux:input
                        wire:model="email"
                        type="email"
                        label="Email"
                        placeholder="ravi@workshop.com"
                        icon="envelope"
                        required
                    />

                    <flux:field>
                        <flux:label>Temporary Password</flux:label>
                        <flux:input.group>
                            <flux:input
                                wire:model="password"
                                placeholder="Leave blank to auto-generate"
                                class:input="font-mono tracking-wide"
                            />
                            <flux:button icon="arrow-path" wire:click="regeneratePassword" type="button">
                                Generate
                            </flux:button>
                        </flux:input.group>
                        <flux:description>
                            Min 8 characters. If blank we'll generate a 12-char password and show it on save.
                        </flux:description>
                        <flux:error name="password" />
                    </flux:field>

                    <flux:select
                        wire:model="selectedRoles"
                        label="Roles"
                        variant="listbox"
                        multiple
                        searchable
                        placeholder="No roles assigned"
                    >
                        @foreach ($roles as $role)
                            <flux:select.option :value="$role->id">{{ $role->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="user-plus">Create user</flux:button>
                </div>
            @endif
        </form>
    </flux:modal>
</div>
