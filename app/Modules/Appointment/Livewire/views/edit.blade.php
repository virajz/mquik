<div>
    <form wire:submit="save" novalidate class="max-w-4xl">
        {{-- Page header --}}
        <div class="mb-8 flex items-start justify-between gap-4">
            <div>
                <flux:link :href="route('appointment.index')" variant="ghost" class="text-xs">
                    <flux:icon.chevron-left class="inline size-3 -mt-0.5" />
                    Appointments
                </flux:link>
                <flux:heading size="xl" level="1" class="mt-1">
                    {{ $editingId ? 'Appointment '.$appointment_no : 'New Appointment' }}
                </flux:heading>
            </div>
            @if ($editingId)
                <div class="flex items-center gap-3">
                    {{-- Derived from what has happened to the car, never typed. --}}
                    <flux:badge :color="match ($status) {
                        'pending' => 'amber',
                        'confirmed' => 'blue',
                        'vehicle_collected' => 'indigo',
                        'arrived' => 'purple',
                        'completed' => 'lime',
                        'cancelled' => 'zinc',
                        'no_show' => 'red',
                        default => 'zinc',
                    }" size="lg">{{ \App\Modules\Appointment\Models\Appointment::statuses()[$status] ?? $status }}</flux:badge>

                    @if ($cancelled_at)
                        <flux:button size="sm" variant="ghost" wire:click="restoreAppointment">Restore</flux:button>
                    @elseif ($status !== \App\Modules\Appointment\Models\Appointment::STATUS_COMPLETED)
                        <flux:button size="sm" variant="ghost" wire:click="confirmCancel">Cancel Booking</flux:button>
                    @endif
                </div>
            @endif
        </div>

        <flux:separator />

        {{-- BOOKING --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Booking</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">When the appointment is for, how it came in, and its current state.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                @if (count($this->schedulingWarnings))
                    <flux:callout variant="warning" icon="exclamation-triangle" inline>
                        <flux:callout.heading>Heads up</flux:callout.heading>
                        <flux:callout.text>
                            @foreach ($this->schedulingWarnings as $warning)
                                <div>{{ $warning }}</div>
                            @endforeach
                            You can still book — this is only a warning.
                        </flux:callout.text>
                    </flux:callout>
                @endif

                {{-- One grid for the whole section: every field shares the same two
                     column edges, so nothing sits at its own width. --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:date-picker locale="en-IN"
                        wire:model.live="appointment_date"
                        label="Appointment Date"
                        placeholder="Select date"
                        required
                        with-today
                        selectable-header
                        fixed-weeks
                        type="input"
                    />
                    {{-- Live so a pickup/drop slot that contradicts it warns immediately. --}}
                    <flux:time-picker
                        wire:model.live="appointment_time"
                        label="Appointment Time"
                        placeholder="Select time"
                        required
                        type="input"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="booking_channel_id" variant="listbox" label="Booking Channel" placeholder="How did it come in?" required>
                        @foreach ($this->bookingChannels as $channel)
                            <flux:select.option :value="$channel->id" wire:key="chan-{{ $channel->id }}">{{ $channel->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="priority_id" variant="listbox" label="Priority" placeholder="Normal" required>
                        @foreach ($this->priorities as $priority)
                            <flux:select.option :value="$priority->id" wire:key="prio-{{ $priority->id }}">{{ $priority->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    {{-- Setting a reason here is what puts the booking in Pending; clearing
                         it lets the status fall back to whatever the car is actually doing.
                         Spans the full row — reasons are sentences, and half a column
                         minus two buttons clipped them after a few words. --}}
                    <flux:field class="md:col-span-2">
                        <flux:label>Pending Reason</flux:label>
                        <div class="flex items-stretch gap-2">
                            <div class="flex-1 min-w-0">
                                <flux:select wire:model="pending_reason_id" variant="combobox" clearable class="w-full">
                                    <x-slot name="input">
                                        <flux:select.input wire:model="pendingReasonSearch" placeholder="Pick or type to add…" />
                                    </x-slot>
                                    @foreach ($this->pendingReasons as $reason)
                                        <flux:select.option :value="$reason->id" wire:key="pnd-{{ $reason->id }}">{{ $reason->name }}</flux:select.option>
                                    @endforeach
                                    @can('pending_reason_master.create')
                                        <flux:select.option.create wire:click="createPendingReason" min-length="2">
                                            Create "<span wire:text="pendingReasonSearch"></span>"
                                        </flux:select.option.create>
                                    @endcan
                                </flux:select>
                            </div>
                            @can('pending_reason_master.create')
                                <flux:tooltip content="Save what you typed as a new reason">
                                    <flux:button icon="plus" variant="ghost" type="button" wire:click="createPendingReason" />
                                </flux:tooltip>
                            @endcan
                            @can('pending_reason_master.view')
                                <flux:tooltip content="Open Pending Reason Master in a new tab">
                                    <flux:button icon="arrow-top-right-on-square" variant="ghost" type="button"
                                        :href="route('pending-reason-master.index')" target="_blank" />
                                </flux:tooltip>
                            @endcan
                        </div>
                    </flux:field>
                </div>
            </div>
        </section>

        {{-- LINKED RECORDS — what this booking has actually become. The form
             offered to create a job card or a pickup/drop but never showed the
             ones already there, so navigation only ever ran forward. --}}
        @if (count($this->linkedRecords))
            <flux:separator />

            <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
                <div>
                    <flux:heading size="lg">Linked Records</flux:heading>
                    <flux:text size="sm" class="mt-1 text-zinc-500">What this booking has turned into so far.</flux:text>
                </div>
                <div class="min-w-0 divide-y divide-zinc-200 dark:divide-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    @foreach ($this->linkedRecords as $link)
                        <div class="flex items-center gap-3 px-4 py-2.5" wire:key="link-{{ $link['type'] }}-{{ $link['label'] }}">
                            <span class="w-28 shrink-0 text-xs text-zinc-500">{{ $link['type'] }}</span>
                            <flux:link :href="$link['url']" wire:navigate class="font-mono text-sm">{{ $link['label'] }}</flux:link>
                            @if ($link['meta'])
                                <flux:badge size="sm" color="zinc">{{ $link['meta'] }}</flux:badge>
                            @endif
                            <flux:icon.arrow-top-right-on-square class="ms-auto size-3.5 text-zinc-400" />
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <flux:separator />

        {{-- CUSTOMER & VEHICLE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Customer & Vehicle</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Pick the customer first — vehicle dropdown filters to that customer's vehicles.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:field>
                    <flux:label>Customer</flux:label>
                    <div class="flex items-stretch gap-2">
                        <div class="flex-1 min-w-0">
                            <flux:select
                                wire:model.live="customer_id"
                                variant="listbox"
                                searchable
                                required
                                :filter="false"
                                placeholder="Pick a customer…"
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
                        @can('customer_master.view')
                            {{-- New tab on purpose: looking a customer up must never
                                 cost the coordinator the booking they are part-way through. --}}
                            <flux:tooltip content="Open Customer Master in a new tab">
                                <flux:button
                                    icon="arrow-top-right-on-square"
                                    variant="ghost"
                                    type="button"
                                    :href="$customer_id ? route('customer-master.edit', $customer_id) : route('customer-master.index')"
                                    target="_blank"
                                />
                            </flux:tooltip>
                        @endcan
                    </div>
                    <flux:error name="customer_id" />
                </flux:field>

                {{-- Same shape as the Customer field above so both pickers end at
                     the same edge and their buttons sit in one column. --}}
                <flux:field>
                    <flux:label>Vehicle</flux:label>
                    <div class="flex items-stretch gap-2">
                        <div class="flex-1 min-w-0">
                            <flux:select
                                wire:model.live="customer_vehicle_id"
                                variant="listbox"
                                searchable
                                clearable
                                required
                                :filter="false"
                                placeholder="Search a vehicle (owner auto-fills)…"
                            >
                                <x-slot name="search">
                                    <flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Registration no…" />
                                </x-slot>
                                @foreach ($this->customerVehicles as $v)
                                    <flux:select.option :value="$v['id']" wire:key="cv-{{ $v['id'] }}">{{ $v['label'] }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                        @can('customer_vehicle_master.view')
                            <flux:tooltip content="Open Vehicle Master in a new tab">
                                <flux:button
                                    icon="arrow-top-right-on-square"
                                    variant="ghost"
                                    type="button"
                                    :href="$customer_vehicle_id ? route('customer-vehicle-master.edit', $customer_vehicle_id) : route('customer-vehicle-master.index')"
                                    target="_blank"
                                />
                            </flux:tooltip>
                        @endcan
                    </div>
                    <flux:error name="customer_vehicle_id" />
                </flux:field>
            </div>
        </section>

        <flux:separator />

        {{-- SERVICE & ROUTING --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Service & Routing</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">What kind of service, which department handles it, and who owns it.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                {{-- Department first: it decides which service types exist at all,
                     so asking for the service type before it would offer a list the
                     next answer can invalidate. --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- A visit can span departments; the first picked is the
                         primary that routes the job card. --}}
                    <flux:select wire:model.live="department_ids" variant="listbox" multiple searchable label="Departments" placeholder="Pick one or more…" required>
                        @foreach ($this->workshopDepartments as $d)
                            <flux:select.option :value="(string) $d->id" wire:key="wd-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    {{-- Keyed to its own option set. Flux's "No results found" row is
                         wire:ignore and hidden by JS, so when Livewire morphs new
                         options into a picker that rendered empty, the element never
                         re-initialises and the stale empty row sits above real
                         results. A changing key makes Livewire replace it instead. --}}
                    <flux:select wire:key="service-type-{{ $this->serviceTypes->pluck('id')->implode('-') }}"
                        wire:model="service_type_id" variant="listbox" searchable clearable
                        label="Service Type"
                        :placeholder="count($department_ids) ? 'Pick a service type…' : 'Pick a department first'"
                        :disabled="! count($department_ids)">
                        @foreach ($this->serviceTypes as $st)
                            <flux:select.option :value="$st->id" wire:key="st-{{ $st->id }}">{{ $st->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- Staff follow the department, so both pickers wait for it. --}}
                @php($staff = $this->employeesByDepartment)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:key="advisor-{{ $staff['advisors']->pluck('id')->implode('-') }}"
                        wire:model="assigned_advisor_id" variant="listbox" searchable
                        label="Advisor"
                        :placeholder="count($department_ids) ? 'Pick an advisor…' : 'Pick a department first'"
                        :disabled="! count($department_ids)"
                        required>
                        @foreach ($staff['advisors'] as $e)
                            <flux:select.option :value="$e->id" wire:key="adv-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:key="technician-{{ $staff['technicians']->pluck('id')->implode('-') }}"
                        wire:model="assigned_technician_id" variant="listbox" searchable clearable
                        label="Technician (optional)"
                        :placeholder="count($department_ids) ? 'Auto-assign later or pick now…' : 'Pick a department first'"
                        :disabled="! count($department_ids)">
                        @foreach ($staff['technicians'] as $e)
                            <flux:select.option :value="$e->id" wire:key="tech-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- Say it out loud rather than quietly offering the wrong people. --}}
                @if ($staff['fellBack'])
                    <flux:text size="sm" class="text-amber-600">
                        No staff are mapped to the selected department, so every advisor and technician is listed. Map them in Employee Master to narrow this.
                    </flux:text>
                @endif
            </div>
        </section>

        <flux:separator />

        {{-- PICKUP --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Pickup / Drop</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">How the vehicle reaches and leaves the workshop. Options where we move the vehicle need an address.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:select wire:model.live="pickup_drop_option_id" variant="listbox" label="Pickup/Drop Option" placeholder="Choose an option…" required>
                    @foreach ($this->pickupDropOptions as $option)
                        <flux:select.option :value="$option->id" wire:key="pdopt-{{ $option->id }}">{{ $option->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                {{-- Distance drives the slab, and the slab quotes the charge —
                     same mechanic as the PickupDrop job, asked once here so the
                     coordinator can price the trip while the customer is on the
                     line. The charge stays editable; a slab is a default price. --}}
                @if ($this->optionInvolvesPickup() || $this->optionInvolvesDrop())
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <flux:input type="number" step="0.01" min="0"
                            wire:model.live.debounce.500ms="distance_km"
                            label="Distance (KM)" placeholder="12" />

                        <flux:select wire:model="distance_slab_id" variant="listbox" clearable
                            label="Distance Slab" placeholder="Auto from distance">
                            @foreach ($this->distanceSlabs as $slab)
                                <flux:select.option :value="$slab->id" wire:key="dslab-{{ $slab->id }}">
                                    {{ $slab->name }} · ₹{{ number_format((float) $slab->charge_amount, 2) }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:field>
                            <flux:label>Pickup / Drop Charge</flux:label>
                            <flux:input.group>
                                <flux:input.group.prefix>₹</flux:input.group.prefix>
                                <flux:input wire:model="distance_charge" inputmode="decimal" placeholder="200.00" />
                            </flux:input.group>
                            <flux:description>Filled from the slab — override if agreed otherwise.</flux:description>
                            <flux:error name="distance_charge" />
                        </flux:field>
                    </div>
                @endif

                {{-- Slots belong to the legs the workshop actually drives, so they
                     live here rather than in Booking, and each appears only when
                     its leg is part of the chosen option. --}}
                @if ($this->optionInvolvesPickup() || $this->optionInvolvesDrop())
                    @php($slotWarnings = $this->slotWarnings)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                        @if ($this->optionInvolvesPickup())
                            <flux:select wire:model.live="time_slot_id" variant="listbox"
                                label="Pickup Time Slot" placeholder="Pick a slot" required>
                                @foreach ($this->timeSlots as $slot)
                                    {{-- Both legs share the slot's capacity, so the
                                         breakdown says where the load came from. --}}
                                    <flux:select.option :value="$slot['id']" wire:key="pslot-{{ $slot['id'] }}">
                                        {{ $slot['label'] }} · {{ $slot['isFull'] ? 'FULL' : max($slot['capacity'] - $slot['booked'], 0).' left' }}
                                        @if ($slot['booked'])
                                            ({{ $slot['pickups'] }} pickup / {{ $slot['drops'] }} drop)
                                        @endif
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            @foreach ($slotWarnings['pickup'] as $warning)
                                <flux:text size="sm" class="mt-1.5 text-amber-600">{{ $warning }}</flux:text>
                            @endforeach
                        @endif

                        @if ($this->optionInvolvesDrop())
                            <flux:select wire:model.live="drop_time_slot_id" variant="listbox"
                                label="Drop Time Slot" placeholder="Pick a slot" required>
                                @foreach ($this->timeSlots as $slot)
                                    <flux:select.option :value="$slot['id']" wire:key="dslot-{{ $slot['id'] }}">
                                        {{ $slot['label'] }} · {{ $slot['isFull'] ? 'FULL' : max($slot['capacity'] - $slot['booked'], 0).' left' }}
                                        @if ($slot['booked'])
                                            ({{ $slot['pickups'] }} pickup / {{ $slot['drops'] }} drop)
                                        @endif
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            @foreach ($slotWarnings['drop'] as $warning)
                                <flux:text size="sm" class="mt-1.5 text-amber-600">{{ $warning }}</flux:text>
                            @endforeach
                        @endif
                    </div>

                    {{-- The vehicle's day in order. Three times spread across two
                         sections read as unrelated fields; in sequence the
                         relationship explains itself. --}}
                    @if (count($this->scheduleTimeline))
                        <div class="mt-4 flex flex-wrap items-center gap-x-2 gap-y-3 rounded-lg border border-zinc-200 dark:border-zinc-700 px-4 py-3">
                            @foreach ($this->scheduleTimeline as $step)
                                @if (! $loop->first)
                                    <flux:icon.arrow-right class="size-4 text-zinc-300 dark:text-zinc-600" />
                                @endif
                                <div class="{{ $step['ok'] ? '' : 'text-amber-600' }}">
                                    <div class="text-sm font-medium {{ $step['ok'] ? 'text-zinc-800 dark:text-white' : '' }}">
                                        {{ $step['time'] }}
                                        @unless ($step['ok'])
                                            <flux:icon.exclamation-triangle class="inline size-3.5 -mt-0.5" />
                                        @endunless
                                    </div>
                                    <div class="text-xs {{ $step['ok'] ? 'text-zinc-500' : '' }}">{{ $step['label'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif

                @if ($this->optionInvolvesPickup())
                    @if ($this->customerAddresses->isNotEmpty())
                        <flux:select wire:model.live="pickup_address_choice" variant="listbox" label="Saved address">
                            @foreach ($this->customerAddresses as $addr)
                                <flux:select.option :value="(string) $addr['id']" wire:key="addr-{{ $addr['id'] }}">
                                    {{ $addr['label'] ?? 'Address' }}{{ $addr['is_primary'] ? ' (Primary)' : '' }} — {{ \Illuminate\Support\Str::limit($addr['full'], 60) }}
                                </flux:select.option>
                            @endforeach
                            <flux:select.option value="custom">Other / type a new address…</flux:select.option>
                        </flux:select>
                    @endif

                    <flux:textarea
                        wire:model="pickup_address"
                        label="{{ $this->customerAddresses->isNotEmpty() && $pickup_address_choice !== 'custom' ? 'Pickup Address (preview — edit if needed)' : 'Pickup Address' }}"
                        placeholder="House / street / area / pincode"
                        rows="2"
                        required
                    />

                    @include('partials.region-pickers', ['leg' => 'pickup'])

                    <flux:field>
                        <flux:label>Pickup Contact Phone</flux:label>
                        <flux:input.group>
                            <flux:input.group.prefix>+91</flux:input.group.prefix>
                            <flux:input
                                wire:model="pickup_contact_phone"
                                mask="99999 99999"
                                placeholder="98765 43210"
                                inputmode="numeric"
                            />
                        </flux:input.group>
                        <flux:description>Defaults to the customer's phone if blank.</flux:description>
                        <flux:error name="pickup_contact_phone" />
                    </flux:field>
                @endif

                {{-- DROP ADDRESS — a car collected from home is often returned to an
                     office, so this is captured separately rather than assumed. --}}
                @if ($this->optionInvolvesDrop())
                    <flux:separator variant="subtle" />

                    @if ($this->customerAddresses->isNotEmpty())
                        <flux:select wire:model.live="drop_address_choice" variant="listbox" label="Saved address">
                            @foreach ($this->customerAddresses as $addr)
                                <flux:select.option :value="(string) $addr['id']" wire:key="daddr-{{ $addr['id'] }}">
                                    {{ $addr['label'] ?: 'Address' }}{{ $addr['is_primary'] ? ' · primary' : '' }}
                                </flux:select.option>
                            @endforeach
                            <flux:select.option value="custom">Somewhere else…</flux:select.option>
                        </flux:select>
                    @endif

                    <flux:textarea
                        wire:model.live.debounce.500ms="drop_address"
                        label="{{ $this->customerAddresses->isNotEmpty() && $drop_address_choice !== 'custom' ? 'Drop Address (preview — edit if needed)' : 'Drop Address' }}"
                        placeholder="Where the vehicle should be returned"
                        rows="2"
                        required
                    />
                    <flux:error name="drop_address" />

                    @include('partials.region-pickers', ['leg' => 'drop'])

                    <flux:field>
                        <flux:label>Drop Contact Phone</flux:label>
                        <flux:input.group>
                            <flux:input.group.prefix>+91</flux:input.group.prefix>
                            <flux:input wire:model="drop_contact_phone" mask="99999 99999"
                                placeholder="98765 43210" inputmode="numeric" />
                        </flux:input.group>
                        <flux:description>Defaults to the customer's phone if blank.</flux:description>
                        <flux:error name="drop_contact_phone" />
                    </flux:field>
                @endif
            </div>
        </section>

        <flux:separator />

        {{-- COMPLAINTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Customer Complaints</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Tick the usual jobs for this department, then add anything the customer reported in their own words. These carry into the job card.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                @include('partials.quick-services')

                @forelse ($complaints as $i => $complaint)
                    <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-3 items-end" wire:key="complaint-{{ $i }}">
                        <flux:select wire:model="complaints.{{ $i }}.complaint_type_id" variant="listbox" searchable clearable label="Complaint Type" placeholder="Pick a type…">
                            @foreach ($this->complaintTypes as $type)
                                <flux:select.option :value="$type->id" wire:key="ct-{{ $i }}-{{ $type->id }}">{{ $type->name }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="complaints.{{ $i }}.job_description_id" variant="listbox" searchable clearable label="Job Description" placeholder="Optional…">
                            @foreach ($this->jobDescriptions as $job)
                                <flux:select.option :value="$job->id" wire:key="jd-{{ $i }}-{{ $job->id }}">{{ $job->name }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:button
                            type="button"
                            variant="ghost"
                            icon="trash"
                            wire:click="removeComplaint({{ $i }})"
                        />

                        <div class="md:col-span-3">
                            <flux:input
                                wire:model="complaints.{{ $i }}.description"
                                placeholder="e.g. SUSPENSION NOISE OVER SPEED BREAKERS"
                                required
                            />
                            <flux:error name="complaints.{{ $i }}.description" />
                        </div>
                    </div>
                @empty
                    <flux:text size="sm" class="text-zinc-500">No complaints recorded yet.</flux:text>
                @endforelse

                <flux:button type="button" variant="ghost" icon="plus" size="sm" wire:click="addComplaint">
                    Add complaint
                </flux:button>
            </div>
        </section>

        <flux:separator />

        {{-- NOTES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Notes</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">What the customer told us, and what the workshop needs to know on the day.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                {{-- Kept apart on purpose: anything read back to or printed for the
                     customer comes from their note, never from the internal one. --}}
                <flux:textarea
                    wire:model="customer_note"
                    label="Customer Note"
                    placeholder="What the customer asked us to note — e.g. don't wash the car, call before starting work."
                    rows="3"
                />

                <flux:textarea
                    wire:model="notes"
                    label="Internal Notes"
                    placeholder="For the advisor and technician — access notes, warnings, anything not for the customer."
                    rows="3"
                />
            </div>
        </section>

        <flux:separator />

        {{-- ACTIONS --}}
        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('appointment.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                {{ $editingId ? 'Save Changes' : 'Create Appointment' }}
            </flux:button>
        </div>
    </form>

    {{-- Reuses CustomerMaster's quick-add partial; CanQuickAddCustomer wires the action. --}}
    {{-- Cancelling asks for its reason at the moment of the decision, rather than
         leaving a reason field sitting on a form for a state nobody has chosen. --}}
    <flux:modal name="cancel-appointment" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Cancel this booking?</flux:heading>
                <flux:subheading>The appointment stays on file and can be restored.</flux:subheading>
            </div>

            <flux:select wire:model="cancel_reason_id" variant="listbox" label="Cancel Reason" placeholder="Why is it being cancelled?">
                @foreach ($this->cancelReasons as $reason)
                    <flux:select.option :value="$reason->id" wire:key="cxl-{{ $reason->id }}">{{ $reason->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="cancel_reason_id" />

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Keep it</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="cancelAppointment">Cancel Booking</flux:button>
            </div>
        </div>
    </flux:modal>

    @include('customer-master::_quick_add_modal')
</div>
