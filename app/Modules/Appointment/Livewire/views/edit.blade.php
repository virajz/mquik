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
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:date-picker
                        wire:model="appointment_date"
                        label="Date"
                        placeholder="Select date"
                        with-today
                        selectable-header
                        fixed-weeks
                        type="input"
                    />
                    <flux:time-picker
                        wire:model="appointment_time"
                        label="Time"
                        placeholder="Select time"
                        type="input"
                    />
                    <flux:select wire:model="channel" variant="listbox" label="Channel" required>
                        @foreach (\App\Modules\Appointment\Models\Appointment::channels() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:select wire:model="status" variant="listbox" label="Status" class="md:max-w-xs" required>
                    @foreach (\App\Modules\Appointment\Models\Appointment::statuses() as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
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
                                placeholder="Pick a customer…"
                            >
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
                    wire:model="customer_vehicle_id"
                    variant="listbox"
                    searchable
                    label="Vehicle"
                    :placeholder="$customer_id ? 'Pick a vehicle…' : 'Pick a customer first'"
                    :disabled="! $customer_id"
                    required
                >
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
                <flux:heading size="lg">Pickup</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Toggle on if we're collecting the vehicle from the customer.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:switch
                    wire:model.live="requires_pickup"
                    label="Requires pickup"
                />

                @if ($requires_pickup)
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
