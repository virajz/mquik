<div>
    <flux:modal name="vehicle-variant-master-form" :dismissible="false" class="md:w-lg">
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
                            <flux:select wire:model="model_id" variant="listbox" placeholder="Select model" searchable :filter="false">
                            <x-slot name="search">
                                <flux:select.search wire:model.live.debounce.250ms="modelSearch" placeholder="Type a brand or model…" />
                            </x-slot>
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
                    <flux:select wire:model="transmission_type_id" label="Transmission" variant="listbox" placeholder="Optional" clearable searchable>
                        @foreach ($this->transmissionTypes as $tt)
                            <flux:select.option :value="$tt->id" wire:key="tt-{{ $tt->id }}">{{ $tt->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="fuel_type_id" label="Fuel Type" variant="listbox" placeholder="Optional" clearable searchable>
                        @foreach ($this->fuelTypes as $ft)
                            <flux:select.option :value="$ft->id" wire:key="ft-{{ $ft->id }}">{{ $ft->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input wire:model="engine_cc" label="Engine" placeholder="e.g. 1197cc" maxlength="20" />
                    <flux:input wire:model="year" label="Year" type="number" min="1980" :max="now()->year + 1" placeholder="e.g. 2024" />
                </div>

                {{-- Service interval — blank inherits the model's. --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Service Interval (KM)</flux:label>
                        <flux:input.group>
                            <flux:input
                                wire:model="service_interval_km"
                                type="number"
                                min="0"
                                placeholder="{{ $this->modelInterval['km'] ? 'Inherits '.number_format($this->modelInterval['km']) : 'From model' }}"
                                class:input="text-right font-mono"
                            />
                            <flux:input.group.suffix>km</flux:input.group.suffix>
                        </flux:input.group>
                        <flux:error name="service_interval_km" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Service Interval (Months)</flux:label>
                        <flux:input.group>
                            <flux:input
                                wire:model="service_interval_months"
                                type="number"
                                min="0"
                                placeholder="{{ $this->modelInterval['months'] ? 'Inherits '.$this->modelInterval['months'] : 'From model' }}"
                                class:input="text-right font-mono"
                            />
                            <flux:input.group.suffix>mo</flux:input.group.suffix>
                        </flux:input.group>
                        <flux:description>Leave blank to inherit the model&rsquo;s interval.</flux:description>
                        <flux:error name="service_interval_months" />
                    </flux:field>
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" />

                {{-- COPY SPARES — create only. Attach a source variant's whole
                     compatibility list, then prune the parts that don't apply. --}}
                @unless ($editingId)
                    <flux:separator variant="subtle" />

                    <flux:field>
                        <flux:label>Copy Spares From <flux:badge size="sm" color="zinc">Optional</flux:badge></flux:label>
                        <flux:description>Pull every spare listed for an existing variant, then remove any that don&rsquo;t fit this one.</flux:description>
                        <flux:select
                            wire:model.live="copy_from_variant_id"
                            variant="listbox"
                            searchable
                            clearable
                            :filter="false"
                            placeholder="Pick a variant to copy from…"
                        >
                            <x-slot name="search">
                                <flux:select.search wire:model.live.debounce.250ms="copyFromSearch" placeholder="Type a brand, model or variant…" />
                            </x-slot>
                            @forelse ($this->sourceVariants as $sv)
                                <flux:select.option :value="$sv->id" wire:key="src-{{ $sv->id }}">
                                    {{ $sv->model?->brand?->name }} {{ $sv->model?->name }} — {{ $sv->name }}
                                </flux:select.option>
                            @empty
                                <flux:select.option value="" disabled>No matching variants.</flux:select.option>
                            @endforelse
                        </flux:select>
                    </flux:field>

                    @if ($copy_from_variant_id)
                        @if (count($clonedSpares))
                            <div class="rounded-md border border-zinc-200 dark:border-zinc-700 p-3">
                                <div class="mb-2 flex items-center justify-between">
                                    <flux:text size="sm" class="font-medium">
                                        {{ count($clonedSpares) }} spare{{ count($clonedSpares) === 1 ? '' : 's' }} will be copied
                                    </flux:text>
                                </div>
                                <div class="flex flex-wrap gap-1.5 max-h-40 overflow-y-auto">
                                    @foreach ($clonedSpares as $spare)
                                        <flux:badge size="sm" wire:key="cln-{{ $spare['id'] }}">
                                            {{ $spare['name'] }}
                                            <flux:badge.close wire:click="removeClonedSpare({{ $spare['id'] }})" />
                                        </flux:badge>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <flux:text size="sm" class="text-zinc-500">That variant has no spares mapped, so nothing will be copied.</flux:text>
                        @endif
                    @endif
                @endunless

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
