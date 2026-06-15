<div>
    <flux:modal name="photo-type-master-form" class="md:w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Photo Type' : 'New Photo Type' }}
                </flux:heading>
                <flux:subheading>
                    Photo categories captured against job cards — odometer, damage close-up, engine bay, VIN plate.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Type Name"
                            placeholder="e.g. ODOMETER"
                            required
                            autofocus
                        />
                    </div>

                    <flux:input
                        wire:model="code"
                        label="Code"
                        placeholder="ENG"
                        maxlength="20"
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="group"
                            label="Tab / Group"
                            placeholder="e.g. EXTERIOR"
                            description="Groups slots into capture tabs on the job card."
                            class:input="uppercase"
                            required
                        />
                    </div>
                    <flux:input
                        wire:model="sort_order"
                        type="number"
                        min="0"
                        label="Order"
                        placeholder="0"
                        description="Within the tab."
                        class:input="text-right font-mono"
                    />
                </div>

                <flux:textarea
                    wire:model="notes"
                    label="Notes"
                    placeholder="Anything the team should know about this photo type"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive types won't appear in the photo upload picker."
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
