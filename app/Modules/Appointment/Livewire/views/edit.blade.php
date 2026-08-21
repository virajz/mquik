<div>
    <form wire:submit="save" class="max-w-4xl">
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
                <flux:badge :color="match ($status) {
                    'pending' => 'amber',
                    'confirmed' => 'blue',
                    'completed' => 'lime',
                    'cancelled' => 'zinc',
                    'no_show' => 'red',
                    default => 'zinc',
                }" size="lg">{{ \App\Modules\Appointment\Models\Appointment::statuses()[$status] }}</flux:badge>
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:date-picker
                        wire:model.live="appointment_date"
                        label="Appointment Date"
                        placeholder="Select date"
                        with-today
                        selectable-header
                        fixed-weeks
                        type="input"
                    />
                    <flux:time-picker
                        wire:model="appointment_time"
                        label="Appointment Time"
                        placeholder="Select time"
                        type="input"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select wire:model="booking_channel_id" variant="listbox" label="Booking Channel" placeholder="How did it come in?" required>
                        @foreach ($this->bookingChannels as $channel)
                            <flux:select.option :value="$channel->id" wire:key="chan-{{ $channel->id }}">{{ $channel->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="priority_id" variant="listbox" label="Priority" placeholder="Normal">
                        @foreach ($this->priorities as $priority)
                            <flux:select.option :value="$priority->id" wire:key="prio-{{ $priority->id }}">{{ $priority->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    {{-- Status reveals its reason field client-side (Alpine), no round-trip.
                         Server still nulls the off-state reason on save, so it stays authoritative. --}}
                    <flux:select wire:model="status" variant="listbox" label="Status" required>
                        @foreach (\App\Modules\Appointment\Models\Appointment::statuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div x-show="$wire.status === '{{ \App\Modules\Appointment\Models\Appointment::STATUS_CANCELLED }}'" x-cloak>
                    <flux:select wire:model="cancel_reason_id" variant="listbox" label="Cancel Reason" class="md:max-w-xs">
                        @foreach ($this->cancelReasons as $reason)
                            <flux:select.option :value="$reason->id" wire:key="cxl-{{ $reason->id }}">{{ $reason->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div x-show="$wire.status === '{{ \App\Modules\Appointment\Models\Appointment::STATUS_PENDING }}'" x-cloak>
                    <flux:select wire:model="pending_reason_id" variant="listbox" label="Pending Reason" class="md:max-w-xs" placeholder="Not specified">
                        @foreach ($this->pendingReasons as $reason)
                            <flux:select.option :value="$reason->id" wire:key="pnd-{{ $reason->id }}">{{ $reason->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- CUSTOMER & VEHICLE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Customer & Vehicle</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Pick the customer first — vehicle dropdown filters to that customer's vehicles.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:field>
                    <flux:label>Customer <span class="text-red-500">*</span></flux:label>
                    <div class="flex items-stretch gap-2">
                        <div class="flex-1 min-w-0">
                            <flux:select
                                wire:model.live="customer_id"
                                variant="listbox"
                                searchable
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
                    </div>
                    <flux:error name="customer_id" />
                </flux:field>

                <flux:select
                    wire:model.live="customer_vehicle_id"
                    variant="listbox"
                    searchable
                    clearable
                    :filter="false"
                    label="Vehicle"
                    placeholder="Search a vehicle (owner auto-fills)…"
                    required
                >
                    <x-slot name="search">
                        <flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Registration no…" />
                    </x-slot>
                    @foreach ($this->customerVehicles as $v)
                        <flux:select.option :value="$v['id']" wire:key="cv-{{ $v['id'] }}">{{ $v['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="customer_vehicle_id" />
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="service_type_id" variant="listbox" searchable clearable label="Service Type" placeholder="Pick a service type…">
                        @foreach ($this->serviceTypes as $st)
                            <flux:select.option :value="$st->id" wire:key="st-{{ $st->id }}">{{ $st->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="workshop_department_id" variant="listbox" searchable label="Department" placeholder="Pick a department…" required>
                        @foreach ($this->workshopDepartments as $d)
                            <flux:select.option :value="$d->id" wire:key="wd-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="assigned_advisor_id" variant="listbox" searchable label="Advisor" placeholder="Pick an advisor…" required>
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="adv-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="assigned_technician_id" variant="listbox" searchable clearable label="Technician (optional)" placeholder="Auto-assign later or pick now…">
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="tech-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if ($this->optionInvolvesPickup())
                            <flux:select wire:model.live="time_slot_id" variant="listbox"
                                label="Pickup Time Slot" placeholder="No specific slot">
                                @foreach ($this->timeSlots as $slot)
                                    <flux:select.option :value="$slot['id']" wire:key="pslot-{{ $slot['id'] }}">
                                        {{ $slot['label'] }} · {{ $slot['isFull'] ? 'FULL' : max($slot['capacity'] - $slot['booked'], 0).' left' }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        @endif

                        @if ($this->optionInvolvesDrop())
                            <flux:select wire:model.live="drop_time_slot_id" variant="listbox"
                                label="Drop Time Slot" placeholder="No specific slot">
                                @foreach ($this->timeSlots as $slot)
                                    <flux:select.option :value="$slot['id']" wire:key="dslot-{{ $slot['id'] }}">
                                        {{ $slot['label'] }} · {{ $slot['isFull'] ? 'FULL' : max($slot['capacity'] - $slot['booked'], 0).' left' }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        @endif
                    </div>
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

                    @include('appointment::_region_pickers', ['leg' => 'pickup'])

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

                    @include('appointment::_region_pickers', ['leg' => 'drop'])

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
                <flux:text size="sm" class="mt-1 text-zinc-500">What the customer reported when booking. These carry into the job card.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
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
                <flux:text size="sm" class="mt-1 text-zinc-500">Anything the technician or advisor should know on the day.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:textarea
                    wire:model="notes"
                    label="Internal Notes"
                    placeholder="Customer requested early delivery; specific complaints; access notes."
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
    @include('customer-master::_quick_add_modal')
</div>
