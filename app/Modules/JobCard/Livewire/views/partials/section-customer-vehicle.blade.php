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
                Only vehicles still on site are listed.
                Not arrived yet? <a href="{{ route('gate-in-out.create') }}" class="underline" wire:navigate>Record the inward first</a>.
            </flux:text>

            {{-- The guard captures a plate and little else. These jump straight
                 to the master, pre-searched on that plate, so the advisor
                 completes the record instead of hunting for it. --}}
            @if ($gate_event_id)
                @php($gv = $this->gateVisits->firstWhere('id', $gate_event_id))
                @if ($gv)
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        @if ($gv->customer_vehicle_id)
                            @can('customer_vehicle_master.update')
                                <flux:button size="xs" variant="ghost" icon="pencil-square" target="_blank"
                                    :href="route('customer-vehicle-master.edit', $gv->customer_vehicle_id)">Complete vehicle details</flux:button>
                            @endcan
                            @if ($gv->customer_id)
                                @can('customer_master.update')
                                    <flux:button size="xs" variant="ghost" icon="user" target="_blank"
                                        :href="route('customer-master.edit', $gv->customer_id)">Complete customer details</flux:button>
                                @endcan
                            @endif
                        @else
                            {{-- Walk-in the guard could not match: land on the master
                                 already searching for the plate they wrote down. --}}
                            <flux:badge size="sm" color="amber">Unmatched walk-in</flux:badge>
                            @can('customer_vehicle_master.view')
                                <flux:button size="xs" variant="ghost" icon="magnifying-glass" target="_blank"
                                    :href="route('customer-vehicle-master.index', ['q' => $gv->registration_no])">Find “{{ $gv->registration_no }}”</flux:button>
                            @endcan
                            @can('customer_vehicle_master.create')
                                <flux:button size="xs" variant="ghost" icon="plus" target="_blank"
                                    :href="route('customer-vehicle-master.create')">Add this vehicle</flux:button>
                            @endcan
                        @endif
                    </div>
                @endif
            @endif
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

        @if ($customer_vehicle_id && ($this->vehicleJobCards->isNotEmpty() || $historySearch !== '' || $historyPreset !== ''))
            {{-- What this car has been in for before — the question an advisor asks
                 the moment the vehicle is known, not three tabs later. --}}
            <div class="mt-4 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="flex items-center justify-between gap-3 px-3 py-2 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm">Vehicle History ({{ $this->vehicleJobCards->count() }})</flux:heading>
                    <div class="flex items-center gap-2">
                        <flux:modal.trigger name="last-recommended-service">
                            <flux:button size="xs" variant="ghost" icon="light-bulb">Last recommended</flux:button>
                        </flux:modal.trigger>
                        <flux:link :href="route('job-history.vehicle-timeline', $customer_vehicle_id)" wire:navigate class="text-xs">Full timeline</flux:link>
                    </div>
                </div>

                {{-- Search reaches the parts and labour actually billed, so "oil
                     change" finds the visit even when the job card never said it.
                     The three buttons are the questions asked most at the desk. --}}
                <div class="px-3 py-2 border-b border-zinc-100 dark:border-zinc-800 space-y-2">
                    <flux:input wire:model.live.debounce.300ms="historySearch" size="sm" icon="magnifying-glass" clearable
                        placeholder="Search past spares, labour or complaints…" />
                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach ([
                            'pms' => 'Last PMS',
                            'oil' => 'Oil Change',
                            'alignment' => 'Alignment / Balancing',
                        ] as $key => $label)
                            @php($marker = $this->lastServiceMarkers[$key] ?? null)
                            <flux:button size="xs" wire:key="preset-{{ $key }}"
                                variant="{{ $historyPreset === $key ? 'primary' : 'ghost' }}"
                                wire:click="setHistoryPreset('{{ $key }}')">
                                {{ $label }}
                                @if ($marker)
                                    <span class="text-xs opacity-70">
                                        · {{ $marker['at']?->format('d/m/y') }}@if ($marker['km']) · {{ number_format($marker['km']) }} km @endif
                                    </span>
                                @endif
                            </flux:button>
                        @endforeach
                    </div>
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
                    @if ($this->vehicleJobCards->isEmpty())
                        <div class="px-3 py-4 text-sm text-zinc-500">Nothing matches that search.</div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</section>

{{-- What we told this customer last time and they did not do — the conversation
     to have while the car is here, not after it leaves. --}}
<flux:modal name="last-recommended-service" class="md:w-2xl">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Last Recommended Services</flux:heading>
            <flux:subheading>Raised on earlier visits for this vehicle.</flux:subheading>
        </div>

        @forelse ($this->lastRecommendations as $rec)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3" wire:key="rec-{{ $rec->id }}">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="font-medium">{{ $rec->recommended_service ?: '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">
                            {{ $rec->recommended_at?->format('d/m/Y') }}
                            @if ($rec->recommendation_reason) · {{ $rec->recommendation_reason }} @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if ($rec->estimated_value)
                            <span class="text-sm font-mono">₹{{ number_format((float) $rec->estimated_value, 2) }}</span>
                        @endif
                        <flux:badge size="sm" :color="match ($rec->status) {
                            'converted', 'accepted' => 'lime',
                            'lost', 'declined' => 'zinc',
                            default => 'amber',
                        }">{{ Str::headline($rec->status ?? 'open') }}</flux:badge>
                    </div>
                </div>
                @if ($rec->customer_response)
                    <div class="mt-2 text-xs text-zinc-500">Customer said: {{ $rec->customer_response }}</div>
                @endif
            </div>
        @empty
            <flux:callout variant="secondary" icon="information-circle" inline>
                <flux:callout.text>Nothing has been recommended for this vehicle yet.</flux:callout.text>
            </flux:callout>
        @endforelse

        <div class="flex justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Close</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>
