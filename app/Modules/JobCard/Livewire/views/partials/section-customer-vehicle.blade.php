<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
    <div>
        <flux:heading size="lg">Customer & Vehicle</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Start from the inward — the vehicle and customer come with it.</flux:text>
    </div>
    <div class="space-y-2 min-w-0">
        {{-- The visit comes first: a card is raised against a vehicle that is
             already here, so the inward is the starting point, not an
             afterthought. Every card hangs off an inward, so a vehicle on site is always
             traceable to when it arrived. One inward can carry several cards. --}}
        <div>
            <flux:select wire:model.live="gate_event_id" variant="listbox" searchable clearable :filter="false"
                label="Inward (Gate Entry)" placeholder="Which visit is this card for…" required>
                <x-slot name="search">
                    <flux:select.search wire:model.live.debounce.250ms="gateVisitSearch" placeholder="Gate no or registration…" />
                </x-slot>
                @foreach ($this->gateVisits as $g)
                    @php($car = trim(($g->customerVehicle?->model?->brand?->name ?? '').' '.($g->customerVehicle?->model?->name ?? '')))
                    <flux:select.option :value="$g->id" wire:key="gv-{{ $g->id }}">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-x-2">
                                <span class="font-mono">{{ $g->registration_no }}</span>
                                @if ($car !== '')<span class="font-medium">{{ $car }}</span>@endif
                                @if (! $g->exited_at)
                                    <flux:badge size="sm" color="lime">Still in</flux:badge>
                                @endif
                            </div>
                            <div class="text-xs text-zinc-500">
                                {{ $g->gate_event_no }}
                                @if ($g->entered_at) · in {{ $g->entered_at->format('d/m, h:i A') }} @endif
                            </div>
                        </div>
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="gate_event_id" />
            <flux:text size="sm" class="mt-1 text-zinc-500">
                Not arrived yet? <a href="{{ route('gate-in-out.create') }}" class="underline" wire:navigate>Record the inward first</a>.
            </flux:text>
        </div>

        <flux:select
            wire:model.live="customer_vehicle_id"
            variant="listbox"
            searchable
            :filter="false"
            label="Customer & Vehicle"
            placeholder="Search reg no or customer…"
            required
        >
            <x-slot name="search">
                <flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Registration no or customer name…" />
            </x-slot>
            @foreach ($this->vehiclePickerOptions as $v)
                <flux:select.option :value="$v['id']" wire:key="cv-{{ $v['id'] }}">{{ $v['label'] }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:error name="customer_vehicle_id" />
        <flux:error name="customer_id" />

    </div>
</section>
