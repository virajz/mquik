<div>
    <flux:modal name="vehicle-color-master-form" class="md:w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Color' : 'New Color' }}</flux:heading>
                <flux:subheading>Vehicle paint color used on registration documents.</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <flux:input wire:model="name" label="Color Name" placeholder="e.g. PEARL WHITE" required autofocus />

                <flux:color-picker
                    wire:model="hex_code"
                    label="Swatch"
                    type="input"
                    placeholder="#FFFFFF"
                />

                <flux:textarea wire:model="notes" label="Notes" rows="2" />

                <flux:separator variant="subtle" />

                <flux:switch wire:model="is_active" label="Active" description="Inactive colors won't appear when adding a customer vehicle." />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
