<div>
    <flux:modal name="distance-slab-master-form" :dismissible="false" class="md:w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Distance Slab' : 'New Distance Slab' }}
                </flux:heading>
                <flux:subheading>
                    Pickup/drop distance bands and their charges — 0-5 KM at 200, 6-15 KM at 300.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Slab Name"
                            placeholder="e.g. 0-5 KM"
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
                    <flux:input
                        type="number"
                        wire:model="min_km"
                        label="From (KM)"
                        min="0"
                        required
                    />

                    <flux:input
                        type="number"
                        wire:model="max_km"
                        label="To (KM)"
                        min="0"
                        placeholder="Leave blank"
                        description="Blank = and above."
                    />

                    <flux:field>
                        <flux:label>Charge <span class="text-red-500">*</span></flux:label>
                        <flux:input.group>
                            <flux:input.group.prefix>₹</flux:input.group.prefix>
                            <flux:input wire:model="charge_amount" inputmode="decimal" placeholder="200.00" />
                        </flux:input.group>
                        <flux:error name="charge_amount" />
                    </flux:field>
                </div>

                <flux:textarea
                    wire:model="notes"
                    label="Notes"
                    placeholder="Anything the team should know about this distance slab"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive distance slabs won&rsquo;t appear in appointment dropdowns."
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
