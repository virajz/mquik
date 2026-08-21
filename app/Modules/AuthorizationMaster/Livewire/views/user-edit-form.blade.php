<div>
    <flux:modal name="authorization-master-user-edit" :dismissible="false" class="md:w-lg">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">Edit user</flux:heading>
                <flux:subheading>
                    @if ($linkedType)
                        Linked to the {{ $linkedType }} <strong>{{ $linkedName }}</strong> — changes here update that
                        record too, so the workshop sees one name and one number.
                    @else
                        A standalone login, not linked to an employee or contractor.
                    @endif
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <flux:input wire:model="name" label="Name" placeholder="Full name" required autofocus />

            <flux:input wire:model="email" type="email" label="Email" icon="envelope" required />

            <flux:field>
                <flux:label>Phone</flux:label>
                <flux:input.group>
                    <flux:input.group.prefix>+91</flux:input.group.prefix>
                    <flux:input wire:model="phone" mask="99999 99999" inputmode="numeric" />
                </flux:input.group>
                <flux:error name="phone" />
            </flux:field>

            <flux:separator variant="subtle" />

            <flux:switch wire:model="isActive" label="Active"
                description="An inactive user cannot sign in. Their records stay untouched." />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">Save changes</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
