<div>
    <flux:modal name="vehicle-variant-master-form" class="md:w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Variant' : 'New Variant' }}</flux:heading>
                <flux:subheading>A trim level under a vehicle model (e.g. Swift VXi, Creta SX).</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Model <span class="text-red-500">*</span></flux:label>
                    <div class="flex items-stretch gap-2">
                        <div class="flex-1 min-w-0">
                            <flux:select wire:model="model_id" variant="listbox" placeholder="Select model" searchable>
                                @foreach ($this->models as $m)
                                    <flux:select.option :value="$m->id">{{ $m->brand?->name }} {{ $m->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                        @can('vehicle_model_master.create')
                            <flux:tooltip content="Quick add a new model">
                                <flux:button
                                    icon="plus"
                                    variant="ghost"
                                    type="button"
                                    x-on:click="$flux.modal('vehicle-model-quick-add').show()"
                                />
                            </flux:tooltip>
                        @endcan
                    </div>
                    <flux:error name="model_id" />
                </flux:field>

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

    {{-- Model quick-add wizard — backed by the CanQuickAddModel trait. --}}
    @can('vehicle_model_master.create')
        @include('vehicle-model-master::_quick_add_modal')
    @endcan
</div>
