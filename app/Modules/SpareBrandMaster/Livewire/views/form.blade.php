<div>
    <flux:modal name="spare-brand-master-form" :dismissible="false" class="md:w-md">
        <form wire:submit="save" novalidate class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Brand' : 'New Brand' }}
                </flux:heading>
                <flux:subheading>
                    {{ $editingId
                        ? 'Update the brand details below.'
                        : 'Add a spare-parts brand the workshop deals with.' }}
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Brand Name"
                            placeholder="e.g. BOSCH"
                            required
                            autofocus
                        />
                    </div>

                    <flux:input
                        wire:model="code"
                        label="Code"
                        placeholder="BSH"
                        maxlength="20"
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                <flux:textarea
                    wire:model="notes"
                    label="Notes"
                    placeholder="Anything the team should know about this brand"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive brands won't appear in spare and inquiry dropdowns."
                />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">
                    {{ $editingId ? 'Save changes' : 'Create' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
