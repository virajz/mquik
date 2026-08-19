{{-- Quick-add for a car nobody has seen before: the five things knowable at
     the barrier. Backed by App\Concerns\CanQuickAddCustomerVehicle. --}}
<flux:modal name="customer-vehicle-quick-add" class="md:w-lg">
    <div class="space-y-5">
        <div>
            <flux:heading size="lg">New vehicle</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">
                For a car that isn't in the system yet. The rest of the detail can be filled in later on the vehicle record.
            </flux:text>
        </div>

        <flux:separator variant="subtle" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <flux:input wire:model="quickCV.name" label="Customer Name" placeholder="Who owns it" required autofocus />
            <flux:field>
                <flux:label>Phone</flux:label>
                <flux:input.group>
                    <flux:input.group.prefix>+91</flux:input.group.prefix>
                    <flux:input wire:model="quickCV.phone" mask="99999 99999" inputmode="numeric" placeholder="98765 43210" />
                </flux:input.group>
                <flux:error name="quickCV.phone" />
            </flux:field>
        </div>

        <flux:input wire:model="quickCV.registration_no" label="Vehicle Number"
            placeholder="STATE RTO ALPHA NUMBER" class:input="font-mono uppercase tracking-wider" required />
        <flux:error name="quickCV.registration_no" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <flux:select wire:model.live="quickCV.brand_id" variant="combobox" label="Brand" required>
                <x-slot name="input">
                    <flux:select.input wire:model="quickCVBrandSearch" placeholder="Pick or type to add…" />
                </x-slot>
                @foreach ($this->quickCVBrands as $b)
                    <flux:select.option :value="$b->id" wire:key="qcvb-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                @endforeach
                @can('vehicle_brand_master.create')
                    <flux:select.option.create wire:click="createQuickCVBrand" min-length="2">
                        Create "<span wire:text="quickCVBrandSearch"></span>"
                    </flux:select.option.create>
                @endcan
            </flux:select>

            <flux:select wire:model="quickCV.model_id" variant="combobox" label="Model" required
                :disabled="! $quickCV['brand_id']">
                <x-slot name="input">
                    <flux:select.input wire:model="quickCVModelSearch"
                        :placeholder="$quickCV['brand_id'] ? 'Pick or type to add…' : 'Pick a brand first'" />
                </x-slot>
                @foreach ($this->quickCVModels as $m)
                    <flux:select.option :value="$m->id" wire:key="qcvm-{{ $m->id }}">{{ $m->name }}</flux:select.option>
                @endforeach
                @can('vehicle_model_master.create')
                    <flux:select.option.create wire:click="createQuickCVModel" min-length="2">
                        Create "<span wire:text="quickCVModelSearch"></span>"
                    </flux:select.option.create>
                @endcan
            </flux:select>
        </div>
        <flux:error name="quickCV.brand_id" />
        <flux:error name="quickCV.model_id" />

        <flux:separator variant="subtle" />

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" icon="check" wire:click="createQuickCustomerVehicle">Add vehicle</flux:button>
        </div>
    </div>
</flux:modal>
