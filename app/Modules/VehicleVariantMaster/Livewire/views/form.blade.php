<div>
    <flux:modal name="vehicle-variant-master-form" class="md:w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Variant' : 'New Variant' }}</flux:heading>
                <flux:subheading>A trim level under a vehicle model (e.g. Swift VXi, Creta SX).</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <flux:select wire:model="model_id" label="Model" variant="listbox" placeholder="Select model" searchable required>
                    @foreach ($this->models as $m)
                        <flux:select.option :value="$m->id">{{ $m->brand?->name }} {{ $m->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="name" label="Variant Name" placeholder="e.g. VXi" required />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="transmission" label="Transmission" variant="listbox" placeholder="Optional">
                        <flux:select.option value="">— Skip —</flux:select.option>
                        <flux:select.option value="manual">Manual</flux:select.option>
                        <flux:select.option value="automatic">Automatic</flux:select.option>
                        <flux:select.option value="amt">AMT</flux:select.option>
                        <flux:select.option value="cvt">CVT</flux:select.option>
                        <flux:select.option value="dct">DCT</flux:select.option>
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input wire:model="engine_cc" label="Engine" placeholder="e.g. 1197cc" maxlength="20" />
                    <flux:input wire:model="year" label="Year" type="number" min="1980" :max="now()->year + 1" placeholder="e.g. 2024" />
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" />

                <flux:separator variant="subtle" />

                <flux:switch wire:model="is_active" label="Active" />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
