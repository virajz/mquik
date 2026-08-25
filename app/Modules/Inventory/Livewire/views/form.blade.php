<div>
    <flux:modal name="inventory-form" :dismissible="false" class="md:w-md">
        <form wire:submit="save" novalidate class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Inventory' : 'New Inventory' }}
                </flux:heading>
                <flux:subheading>
                    {{ $editingId ? 'Update the details below.' : 'Fill in the details to create a new record.' }}
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <flux:input
                    wire:model="name"
                    label="Name"
                    placeholder="Record name"
                    required
                    autofocus
                />

                <flux:textarea
                    wire:model="description"
                    label="Description"
                    placeholder="Optional notes"
                    rows="3"
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
