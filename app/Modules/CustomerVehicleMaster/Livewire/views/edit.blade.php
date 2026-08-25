<div>
    <form wire:submit="save" novalidate class="max-w-4xl">
        {{-- Page header --}}
        <div class="mb-8 flex items-start justify-between gap-4">
            <div>
                <flux:link :href="route('customer-vehicle-master.index')" variant="ghost" class="text-xs">
                    <flux:icon.chevron-left class="inline size-3 -mt-0.5" />
                    Customer Vehicles
                </flux:link>
                <flux:heading size="xl" level="1" class="mt-1">
                    {{ $editingId ? ($registration_no ?: ($this->isUnregisteredPlate ? 'Unregistered vehicle' : 'Vehicle #'.$editingId)) : 'New Customer Vehicle' }}
                </flux:heading>
            </div>
            @if ($editingId)
                <div class="flex items-center gap-2">
                    @can('job_history.view')
                        <flux:button :href="route('job-history.vehicle-timeline', $editingId)" wire:navigate size="sm" variant="ghost" icon="clock">Timeline</flux:button>
                    @endcan
                    <flux:modal.trigger name="record-panel">
                        <flux:button type="button" size="sm" variant="ghost" icon="squares-2x2">Related</flux:button>
                    </flux:modal.trigger>
                </div>
            @endif
        </div>

        <flux:separator />

        {{-- OWNER --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Owner</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Who this vehicle belongs to.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:field>
                    <flux:label>Customer <span class="text-red-500">*</span></flux:label>
                    <div class="flex items-stretch gap-2">
                        <div class="flex-1 min-w-0">
                            {{-- Backend search: ~9,700 customers, queried per keystroke. --}}
                            <flux:select
                                wire:model="customer_id"
                                variant="listbox"
                                placeholder="Search by name or phone…"
                                searchable
                                :filter="false"
                            >
                                <x-slot name="search">
                                    <flux:select.search
                                        wire:model.live.debounce.250ms="customerSearch"
                                        placeholder="Type a name or phone…"
                                    />
                                </x-slot>
                                @forelse ($this->customers as $c)
                                    <flux:select.option :value="$c->id" wire:key="cust-{{ $c->id }}">{{ $c->name }} — +91 {{ $c->phone }}</flux:select.option>
                                @empty
                                    <flux:select.option value="" disabled>No matching customers.</flux:select.option>
                                @endforelse
                            </flux:select>
                        </div>
                        @can('customer_master.create')
                            <flux:tooltip content="Quick add a new customer">
                                <flux:button
                                    icon="plus"
                                    variant="ghost"
                                    type="button"
                                    x-on:click="$flux.modal('customer-quick-add').show()"
                                />
                            </flux:tooltip>
                        @endcan
                    </div>
                    <flux:error name="customer_id" />
                </flux:field>
            </div>
        </section>

        <flux:separator />

        {{-- VEHICLE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Vehicle</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">
                    Pick the brand + model + variant in one go. Color can be added inline if missing.
                </flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:field>
                    <flux:label>Vehicle <span class="text-red-500">*</span></flux:label>
                    <div class="flex items-stretch gap-2">
                        <div class="flex-1 min-w-0">
                            {{-- Backend search: the catalogue is ~1,800 variants, so
                                 options are queried per keystroke instead of rendered whole. --}}
                            <flux:select
                                wire:model="variant_id"
                                variant="listbox"
                                placeholder="Search by brand, model, or variant…"
                                searchable
                                :filter="false"
                            >
                                <x-slot name="search">
                                    <flux:select.search
                                        wire:model.live.debounce.250ms="vehicleSearch"
                                        placeholder="Type a brand, model or variant…"
                                    />
                                </x-slot>
                                @forelse ($this->vehicles as $v)
                                    <flux:select.option :value="$v->id" wire:key="veh-{{ $v->id }}">{{ $v->label }}</flux:select.option>
                                @empty
                                    <flux:select.option value="" disabled>No matching variants.</flux:select.option>
                                @endforelse
                            </flux:select>
                        </div>
                        @can('vehicle_variant_master.create')
                            <flux:tooltip content="Quick add a new vehicle (brand → model → variant)">
                                <flux:button
                                    icon="plus"
                                    variant="ghost"
                                    type="button"
                                    x-on:click="$flux.modal('vehicle-quick-add').show()"
                                />
                            </flux:tooltip>
                        @endcan
                    </div>
                    <flux:error name="variant_id" />
                </flux:field>

                {{-- Color: single-value picker → combobox + inline create-option per the picker rule. --}}
                <flux:select
                    wire:model="color_id"
                    variant="combobox"
                    label="Color"
                    clearable
                >
                    <x-slot name="input">
                        <flux:select.input wire:model="colorSearch" placeholder="Pick or type to add…" />
                    </x-slot>
                    @foreach ($this->colors as $c)
                        <flux:select.option :value="$c->id" wire:key="color-{{ $c->id }}">
                            <div class="flex items-center gap-2">
                                <span class="size-3 shrink-0 rounded-full border border-zinc-300 dark:border-zinc-600"
                                    style="background-color: {{ $c->hex_code ?? '#cccccc' }}"></span>
                                <span>{{ $c->name }}</span>
                            </div>
                        </flux:select.option>
                    @endforeach
                    @can('vehicle_color_master.create')
                        <flux:select.option.create wire:click="createColor" min-length="2">
                            Create "<span wire:text="colorSearch"></span>"
                        </flux:select.option.create>
                    @endcan
                </flux:select>
            </div>
        </section>

        <flux:separator />

        {{-- REGISTRATION --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Registration</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">
                    Plate number and type. Accepts state series (GJ05RH4816) and BH series (24BH1234AA).
                </flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select wire:model.live="registration_type_id" label="Plate Type" variant="listbox" placeholder="Select…" clearable searchable>
                        @foreach ($this->registrationTypes as $rt)
                            <flux:select.option :value="$rt->id" wire:key="rt-{{ $rt->id }}">{{ $rt->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div class="md:col-span-2">
                        {{-- An unregistered vehicle has no plate yet, so the number
                             is neither asked for nor pattern-checked. --}}
                        @if ($this->isUnregisteredPlate)
                            <flux:field>
                                <flux:label>Registration No.</flux:label>
                                <flux:input value="UNREGISTERED" readonly class:input="font-mono tracking-wide text-zinc-500" />
                                <flux:description>Not required — add the plate here once the vehicle is registered.</flux:description>
                            </flux:field>
                        @else
                            <flux:input
                                wire:model.live.debounce.400ms="registration_no"
                                label="Registration No."
                                placeholder="GJ05RH4816 / 24BH1234AA"
                                maxlength="20"
                                class:input="font-mono uppercase tracking-wide"
                                required
                                x-on:input="$event.target.value = $event.target.value.toUpperCase().replace(/\s+/g, '')"
                            />
                        @endif
                    </div>
                </div>

                <flux:input
                    wire:model="year_of_manufacture"
                    type="number"
                    label="Year of Manufacture"
                    placeholder="2022"
                    min="1980"
                    :max="(int) date('Y') + 1"
                />
            </div>
        </section>

        <flux:separator />

        {{-- IDENTIFIERS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Identifiers & Odometer</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">VIN, engine number, and current odometer reading.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:input
                    wire:model="vin"
                    label="VIN / Chassis No."
                    placeholder="17 characters"
                    maxlength="17"
                    class:input="font-mono uppercase tracking-wide"
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        wire:model="engine_no"
                        label="Engine No."
                        maxlength="30"
                        class:input="font-mono uppercase tracking-wide"
                    />
                    <flux:input
                        wire:model="odometer_km"
                        type="number"
                        label="Odometer (km)"
                        placeholder="45000"
                        min="0"
                    />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- NOTES & STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Notes & Status</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Internal context and visibility.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:textarea
                    wire:model="notes"
                    label="Internal Notes"
                    placeholder="Anything the team should know about this vehicle"
                    rows="3"
                />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive vehicles won't appear in appointment / job card dropdowns."
                />
            </div>
        </section>

        {{-- Sticky bottom action bar --}}
        <div class="sticky bottom-0 -mx-6 lg:-mx-8 px-6 lg:px-8 py-3 mt-4 border-t border-zinc-200 dark:border-zinc-700 bg-white/85 dark:bg-zinc-900/85 backdrop-blur">
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" :href="route('customer-vehicle-master.index')" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary" icon="check">
                    {{ $editingId ? 'Save changes' : 'Create' }}
                </flux:button>
            </div>
        </div>
    </form>

    {{-- Customer quick-add modal — backed by the CanQuickAddCustomer trait. --}}
    @can('customer_master.create')
        @include('customer-master::_quick_add_modal')
    @endcan

    {{-- Vehicle (Variant) quick-add wizard — backed by the CanQuickAddVehicle trait. --}}
    @can('vehicle_variant_master.create')
        @include('customer-vehicle-master::_quick_add_vehicle_modal')
    @endcan

    {{-- Related-areas flyout --}}
    @if ($editingId)
        <livewire:record-panel subject="vehicle" :record-id="$editingId" :record-label="$registration_no" />
    @endif
</div>
