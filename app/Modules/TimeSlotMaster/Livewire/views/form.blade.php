<div>
    <flux:modal name="time-slot-master-form" :dismissible="false" class="md:w-md">
        <form wire:submit="save" novalidate class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Time Slot' : 'New Time Slot' }}
                </flux:heading>
                <flux:subheading>
                    Bookable appointment windows — start/end time, vehicle capacity per slot and optional buffer.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Slot Name"
                            placeholder="e.g. 09:00-10:00"
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input
                        type="time"
                        wire:model="slot_start_time"
                        label="Slot Start Time"
                        required
                    />

                    <flux:input
                        type="time"
                        wire:model="slot_end_time"
                        label="Slot End Time"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input
                        type="number"
                        wire:model="max_vehicles_per_slot"
                        label="Max Vehicles per Slot"
                        min="1"
                        required
                        description="Booking past this warns the advisor but is still allowed."
                    />

                    <flux:input
                        type="number"
                        wire:model="buffer_minutes"
                        label="Buffer (minutes)"
                        min="0"
                        description="Optional changeover gap after the slot."
                    />
                </div>

                <flux:textarea
                    wire:model="notes"
                    label="Notes"
                    placeholder="Anything the team should know about this time slot"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive time slots won&rsquo;t appear in appointment dropdowns."
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
