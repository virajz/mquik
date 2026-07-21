<div>
    <flux:modal name="priority-master-form" class="md:w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Priority' : 'New Priority' }}
                </flux:heading>
                <flux:subheading>
                    Shared urgency levels for appointments, inspection orders and internal part orders.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Priority Name"
                            placeholder="e.g. URGENT"
                            required
                            autofocus
                        />
                    </div>

                    <flux:input
                        wire:model="code"
                        label="Code"
                        placeholder="URG"
                        maxlength="20"
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input
                        type="number"
                        wire:model="sort_order"
                        label="Order"
                        placeholder="10"
                        min="0"
                        description="Lower shows first. Normal 10, High 20, Urgent 30."
                    />

                    <flux:select wire:model="applies_to" variant="listbox" label="Applies To" required>
                        @foreach (\App\Modules\PriorityMaster\Models\PriorityMaster::scopes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:text size="sm" class="text-zinc-500">
                    Workshop jobs = appointments and inspection orders. Parts orders = internal part orders.
                </flux:text>

                <flux:textarea
                    wire:model="notes"
                    label="Notes"
                    placeholder="Anything the team should know about this priority"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive priorities won&rsquo;t appear in appointment dropdowns."
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
