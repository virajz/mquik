{{--
    Vehicle (Variant) quick-add wizard modal — included by any parent component
    using `CanQuickAddVehicle`. Walks brand → model → variant in one modal,
    with inline create-options on Brand and Model.
--}}
<flux:modal name="vehicle-quick-add" class="md:w-lg">
    <form wire:submit.prevent="createQuickVehicle" class="space-y-5">
        <div>
            <flux:heading size="lg">Quick add Vehicle</flux:heading>
            <flux:subheading>Brand → Model → Variant. New brands and models can be added inline.</flux:subheading>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select
                wire:model.live="quickVehicle.brand_id"
                variant="combobox"
                label="Brand"
                required
            >
                <x-slot name="input">
                    <flux:select.input wire:model="quickVehicleBrandSearch" placeholder="Pick or type to add…" />
                </x-slot>
                @foreach ($this->quickVehicleBrands as $b)
                    <flux:select.option :value="$b->id" wire:key="qvb-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                @endforeach
                @can('vehicle_brand_master.create')
                    <flux:select.option.create wire:click="createQuickVehicleBrand" min-length="2">
                        Create "<span wire:text="quickVehicleBrandSearch"></span>"
                    </flux:select.option.create>
                @endcan
            </flux:select>

            <flux:select
                wire:model="quickVehicle.model_id"
                variant="combobox"
                label="Model"
                placeholder="{{ $quickVehicle['brand_id'] ? 'Pick or type to add…' : 'Pick a brand first' }}"
                :disabled="! $quickVehicle['brand_id']"
                required
            >
                <x-slot name="input">
                    <flux:select.input wire:model="quickVehicleModelSearch" placeholder="Pick or type to add…" />
                </x-slot>
                @foreach ($this->quickVehicleModels as $m)
                    <flux:select.option :value="$m->id" wire:key="qvm-{{ $m->id }}">{{ $m->name }}</flux:select.option>
                @endforeach
                @can('vehicle_model_master.create')
                    <flux:select.option.create wire:click="createQuickVehicleModel" min-length="2">
                        Create "<span wire:text="quickVehicleModelSearch"></span>"
                    </flux:select.option.create>
                @endcan
            </flux:select>
        </div>
        <flux:error name="quickVehicle.brand_id" />
        <flux:error name="quickVehicle.model_id" />

        <flux:input
            wire:model="quickVehicle.name"
            label="Variant Name"
            placeholder="e.g. SX 2024 / VXi"
            required
        />
        <flux:error name="quickVehicle.name" />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="quickVehicle.fuel_type_id" label="Fuel" variant="listbox" placeholder="Optional" clearable>
                @foreach ($this->quickVehicleFuelTypes as $ft)
                    <flux:select.option :value="$ft->id" wire:key="qv-ft-{{ $ft->id }}">{{ $ft->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="quickVehicle.transmission_type_id" label="Transmission" variant="listbox" placeholder="Optional" clearable>
                @foreach ($this->quickVehicleTransmissionTypes as $tt)
                    <flux:select.option :value="$tt->id" wire:key="qv-tt-{{ $tt->id }}">{{ $tt->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="quickVehicle.year" type="number" label="Year" min="1980" :max="now()->year + 1" placeholder="2024" />
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <flux:modal.close>
                <flux:button variant="ghost" type="button">Cancel</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" icon="check">Add</flux:button>
        </div>
    </form>
</flux:modal>
