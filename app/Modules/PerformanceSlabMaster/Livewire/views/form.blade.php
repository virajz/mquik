<div>
    <flux:modal name="performance-slab-master-form" :dismissible="false" class="md:w-md">
        <form wire:submit="save" novalidate class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Performance Slab' : 'New Performance Slab' }}
                </flux:heading>
                <flux:subheading>
                    Productivity slabs mapping achievement % to an incentive amount.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Name"
                            placeholder="e.g. SLAB 1"
                            required
                            autofocus
                        />
                    </div>

                    <flux:input
                        wire:model="code"
                        label="Code"
                        placeholder="S1"
                        maxlength="20"
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input type="number" step="0.01" min="0" wire:model="min_percent" label="Min %" />
                    <flux:input type="number" step="0.01" min="0" wire:model="max_percent" label="Max %" placeholder="Blank = open" />
                    <flux:input type="number" step="0.01" min="0" wire:model="incentive_amount" label="Incentive ₹" />
                </div>

                <flux:textarea
                    wire:model="notes"
                    label="Notes"
                    placeholder="Anything the team should know about this slab"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive slabs won't appear in dropdowns."
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
