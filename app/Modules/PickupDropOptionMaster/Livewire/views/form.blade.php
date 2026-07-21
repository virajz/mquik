<div>
    <flux:modal name="pickup-drop-option-master-form" class="md:w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Pickup/Drop Option' : 'New Pickup/Drop Option' }}
                </flux:heading>
                <flux:subheading>
                    How the vehicle reaches and leaves the workshop — self drop, workshop pickup/drop, towing or doorstep inspection.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Option Name"
                            placeholder="e.g. WORKSHOP TOWING"
                            required
                            autofocus
                        />
                    </div>

                    <flux:input
                        wire:model="code"
                        label="Code"
                        placeholder="WTOW"
                        maxlength="20"
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                <flux:textarea
                    wire:model="notes"
                    label="Notes"
                    placeholder="Anything the team should know about this pickup/drop option"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <div class="space-y-3">
                    <flux:switch
                        wire:model="involves_pickup"
                        label="Workshop collects the vehicle"
                        description="Raises a Pickup job when this option is chosen on an appointment."
                    />

                    <flux:switch
                        wire:model="involves_drop"
                        label="Workshop returns the vehicle"
                        description="Raises a Drop job when this option is chosen on an appointment."
                    />
                </div>

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive pickup/drop options won&rsquo;t appear in appointment dropdowns."
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
