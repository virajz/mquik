<div>
    <flux:modal name="vehicle-model-master-form" :dismissible="false" class="md:w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Model' : 'New Model' }}</flux:heading>
                <flux:subheading>A specific vehicle model under a brand (e.g. Maruti Swift, Hyundai Creta).</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="brand_id" label="Brand" variant="combobox" required>
                        <x-slot name="input">
                            <flux:select.input wire:model="brandSearch" placeholder="Pick or type to add…" />
                        </x-slot>
                        @foreach ($this->brands as $b)
                            <flux:select.option :value="$b->id" wire:key="b-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                        @endforeach
                        @can('vehicle_brand_master.create')
                            <flux:select.option.create wire:click="createBrand" min-length="2">
                                Create "<span wire:text="brandSearch"></span>"
                            </flux:select.option.create>
                        @endcan
                    </flux:select>
                    <flux:input wire:model="name" label="Model Name" placeholder="e.g. SWIFT" required />
                </div>

                <flux:select wire:model="vehicle_segment_id" label="Vehicle Type" variant="combobox" clearable>
                    <x-slot name="input">
                        <flux:select.input wire:model="vehicleSegmentSearch" placeholder="Pick or type to add…" />
                    </x-slot>
                    @foreach ($this->segments as $s)
                        <flux:select.option :value="$s->id" wire:key="vs-{{ $s->id }}">{{ $s->name }}</flux:select.option>
                    @endforeach
                    @can('vehicle_segment_master.create')
                        <flux:select.option.create wire:click="createSegment" min-length="2">
                            Create "<span wire:text="vehicleSegmentSearch"></span>"
                        </flux:select.option.create>
                    @endcan
                </flux:select>

                {{-- Service interval — variants inherit this unless they override it. --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Service Interval (KM)</flux:label>
                        <flux:input.group>
                            <flux:input wire:model="service_interval_km" type="number" min="0" placeholder="10000" class:input="text-right font-mono" />
                            <flux:input.group.suffix>km</flux:input.group.suffix>
                        </flux:input.group>
                        <flux:error name="service_interval_km" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Service Interval (Months)</flux:label>
                        <flux:input.group>
                            <flux:input wire:model="service_interval_months" type="number" min="0" placeholder="12" class:input="text-right font-mono" />
                            <flux:input.group.suffix>mo</flux:input.group.suffix>
                        </flux:input.group>
                        <flux:description>Whichever comes first drives the next-service-due date.</flux:description>
                        <flux:error name="service_interval_months" />
                    </flux:field>
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
