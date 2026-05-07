<div>
    <flux:modal name="vehicle-model-master-form" class="md:w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Model' : 'New Model' }}</flux:heading>
                <flux:subheading>A specific vehicle model under a brand (e.g. Maruti Swift, Hyundai Creta).</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="brand_id" label="Brand" variant="listbox" placeholder="Select brand" required>
                        @foreach ($this->brands as $b)
                            <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="name" label="Model Name" placeholder="e.g. SWIFT" required />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="vehicle_segment_id" label="Segment" variant="listbox" searchable placeholder="Optional">
                        <flux:select.option value="">— None —</flux:select.option>
                        @foreach ($this->segments as $s)
                            <flux:select.option :value="$s->id">{{ $s->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="fuel_type" label="Fuel Type" variant="listbox" placeholder="Optional">
                        <flux:select.option value="">— Skip —</flux:select.option>
                        <flux:select.option value="petrol">Petrol</flux:select.option>
                        <flux:select.option value="diesel">Diesel</flux:select.option>
                        <flux:select.option value="cng">CNG</flux:select.option>
                        <flux:select.option value="electric">Electric</flux:select.option>
                        <flux:select.option value="hybrid">Hybrid</flux:select.option>
                    </flux:select>
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" />

                <flux:separator variant="subtle" />

                <flux:switch wire:model="is_active" label="Active" description="Inactive models won't appear when adding a customer vehicle." />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
