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

        {{-- Two pickers, not one: the advisor usually knows the customer, and the
             vehicle list then narrows to that customer's garage. Picking a
             vehicle first still fills the owner in. --}}
        <flux:select
            wire:model.live="customer_id"
            variant="listbox"
            searchable
            clearable
            :filter="false"
            label="Customer"
            placeholder="Search name or phone…"
        >
            <x-slot name="search">
                <flux:select.search wire:model.live.debounce.250ms="customerSearch" placeholder="Name or phone…" />
            </x-slot>
            @foreach ($this->customers as $c)
                <flux:select.option :value="$c->id" wire:key="cust-{{ $c->id }}">
                    {{ trim($c->first_name.' '.($c->last_name ?? '')) }}{{ $c->phone ? ' · '.$c->phone : '' }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:error name="customer_id" />

        <flux:select
            wire:model.live="customer_vehicle_id"
            variant="listbox"
            searchable
            :filter="false"
            label="Vehicle"
            :placeholder="$customer_id ? 'Pick this customer\'s vehicle…' : 'Search registration no…'"
            required
        >
            <x-slot name="search">
                <flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Registration no or customer name…" />
            </x-slot>
            @foreach ($this->vehiclePickerOptions as $v)
                <flux:select.option :value="$v['id']" wire:key="cv-{{ $v['id'] }}">
                    {{ $v['label'] }}@if (! $customer_id && $v['owner']) <span class="text-zinc-500">· {{ $v['owner'] }}</span>@endif
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:error name="customer_vehicle_id" />

        @if ($customer_vehicle_id && $this->vehicleJobCards->isNotEmpty())
            {{-- What this car has been in for before — the question an advisor asks
                 the moment the vehicle is known, not three tabs later. --}}
            <div class="mt-4 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="flex items-center justify-between gap-3 px-3 py-2 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm">Vehicle History ({{ $this->vehicleJobCards->count() }})</flux:heading>
                    <flux:link :href="route('job-history.vehicle-timeline', $customer_vehicle_id)" wire:navigate class="text-xs">Full timeline</flux:link>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 max-h-64 overflow-y-auto">
                    @foreach ($this->vehicleJobCards as $vjc)
                        <div class="px-3 py-2 flex items-start justify-between gap-3 text-sm" wire:key="vh-{{ $vjc->id }}">
                            <div class="min-w-0">
                                <div class="font-medium">
                                    {{ $vjc->billed_at?->format('d/m/Y') ?? 'Not billed' }}
                                    <span class="text-zinc-500 font-normal">· {{ $vjc->invoice_no ?? $vjc->job_card_no }}</span>
                                </div>
                                <div class="text-xs text-zinc-500 truncate">
                                    {{ $vjc->complaints->pluck('description')->filter()->take(3)->implode(' · ') ?: 'No complaints recorded' }}
                                </div>
                            </div>
                            <div class="shrink-0 text-right text-xs text-zinc-500">
                                @if ($vjc->km_at_service) {{ number_format($vjc->km_at_service) }} km @endif
                                <div>{{ $vjc->workshopDepartment?->name }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>