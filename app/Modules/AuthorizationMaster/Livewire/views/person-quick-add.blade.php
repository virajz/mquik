<div>
    <flux:modal name="authorization-master-person-quick-add" :dismissible="false" class="md:w-md">
        <form wire:submit="save" novalidate class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $type === 'employee' ? 'New employee' : 'New service contractor' }}
                </flux:heading>
                <flux:subheading>
                    Just enough to create their login. The rest can be filled in on the
                    {{ $type === 'employee' ? 'Employees' : 'Vendors' }} master later.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <flux:input wire:model="name" label="Name" placeholder="Full name" required autofocus />

            <flux:field>
                <flux:label>Phone</flux:label>
                <flux:input.group>
                    <flux:input.group.prefix>+91</flux:input.group.prefix>
                    <flux:input wire:model="phone" mask="99999 99999" inputmode="numeric" placeholder="98765 43210" />
                </flux:input.group>
                <flux:error name="phone" />
            </flux:field>

            <flux:input wire:model="email" type="email" label="Email" placeholder="Optional" icon="envelope" />

            <flux:separator variant="subtle" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">Add</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
