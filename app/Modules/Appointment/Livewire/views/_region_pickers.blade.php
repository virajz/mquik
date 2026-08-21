{{-- Cascading state → city → area for one address leg ('pickup' or 'drop').

     Only the leaf lands in `{{ $leg }}_region_id`; state and city are pickers,
     not stored columns. Cities and areas can be created from here — the master
     will never hold every locality, and a coordinator on a call cannot detour
     into the region master. States are picked only: that list is seeded whole,
     so anything typed would be a misspelling of one already there. --}}
@php
    $stateProp = $leg.'_state_id';
    $cityProp = $leg.'_city_id';
    $citySearch = $leg.'CitySearch';
    $areaSearch = $leg.'AreaSearch';
    $cities = $leg === 'pickup' ? $this->pickupCities : $this->dropCities;
    $areas = $leg === 'pickup' ? $this->pickupAreas : $this->dropAreas;
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <flux:select wire:model.live="{{ $stateProp }}" variant="listbox" searchable clearable
        label="State" placeholder="Pick a state…">
        @foreach ($this->regionStates as $r)
            <flux:select.option :value="$r->id" wire:key="{{ $leg }}-st-{{ $r->id }}">{{ $r->name }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:select wire:model.live="{{ $cityProp }}" variant="combobox" clearable
        label="City" :disabled="! $$stateProp">
        <x-slot name="input">
            <flux:select.input wire:model="{{ $citySearch }}"
                :placeholder="$$stateProp ? 'Pick or type to add…' : 'Pick a state first'" />
        </x-slot>
        @foreach ($cities as $r)
            <flux:select.option :value="$r->id" wire:key="{{ $leg }}-ct-{{ $r->id }}">{{ $r->name }}</flux:select.option>
        @endforeach
        @can('region_master.create')
            <flux:select.option.create wire:click="create{{ ucfirst($leg) }}City" min-length="2">
                Create "<span wire:text="{{ $citySearch }}"></span>"
            </flux:select.option.create>
        @endcan
    </flux:select>

    <flux:select wire:model="{{ $leg }}_region_id" variant="combobox" clearable
        label="Area" :disabled="! $$cityProp">
        <x-slot name="input">
            <flux:select.input wire:model="{{ $areaSearch }}"
                :placeholder="$$cityProp ? 'Pick or type to add…' : 'Pick a city first'" />
        </x-slot>
        @foreach ($areas as $r)
            <flux:select.option :value="$r->id" wire:key="{{ $leg }}-ar-{{ $r->id }}">{{ $r->name }}</flux:select.option>
        @endforeach
        @can('region_master.create')
            <flux:select.option.create wire:click="create{{ ucfirst($leg) }}Area" min-length="2">
                Create "<span wire:text="{{ $areaSearch }}"></span>"
            </flux:select.option.create>
        @endcan
    </flux:select>
</div>
