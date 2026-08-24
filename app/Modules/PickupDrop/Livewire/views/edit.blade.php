@php($PD = \App\Modules\PickupDrop\Models\PickupDrop::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8 flex items-start justify-between gap-4">
            <div>
                <flux:link :href="route('pickup-drop.index')" variant="ghost" class="text-xs">
                    <flux:icon.chevron-left class="inline size-3 -mt-0.5" />
                    Pickup / Drop
                </flux:link>
                <flux:heading size="xl" level="1" class="mt-1">
                    {{ $editingId ? 'Pickup/Drop '.$pickup_drop_no : 'New Pickup / Drop' }}
                </flux:heading>
                @if ($appointment_id)
                    <flux:text size="sm" class="mt-1 text-zinc-500">Linked to Appointment #{{ $appointment_id }}</flux:text>
                @endif
            </div>
            @if ($editingId)
                <div class="flex items-center gap-3">
                    {{-- Derived from what the driver has done, never typed. --}}
                    <flux:badge :color="match ($status) {
                        'pending' => 'amber', 'driver_assigned' => 'blue', 'driver_on_the_way' => 'sky',
                        'vehicle_collected' => 'indigo', 'vehicle_delivered' => 'lime',
                        'completed' => 'green', 'cancelled' => 'zinc', default => 'zinc',
                    }" size="lg">{{ $PD::statuses()[$status] }}</flux:badge>

                    @if ($cancelled_at)
                        <flux:button size="sm" variant="ghost" wire:click="restorePickupDrop">Restore</flux:button>
                    @elseif ($status !== $PD::STATUS_COMPLETED)
                        <flux:button size="sm" variant="ghost" wire:click="confirmCancel">Cancel Job</flux:button>
                    @endif
                </div>
            @endif
        </div>

        <flux:separator />

        {{-- JOB & SCHEDULE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Job & Schedule</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">What was booked, which leg this job runs, and when.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model.live="pickup_drop_option_id" variant="listbox" label="Pickup/Drop Type" placeholder="What the customer booked…" required>
                        @foreach ($this->pickupDropOptions as $o)
                            <flux:select.option :value="$o->id" wire:key="pdopt-{{ $o->id }}">{{ $o->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

{{-- The leg is read off the type — a pickup-involving type IS the
                         pickup leg; the return trip is its own job later. --}}
                    <flux:field>
                        <flux:label>This Job Is</flux:label>
                        <div class="flex items-center h-10">
                            <flux:badge color="{{ $direction === \App\Modules\PickupDrop\Models\PickupDrop::DIRECTION_PICKUP ? 'blue' : 'indigo' }}" size="sm">
                                {{ \App\Modules\PickupDrop\Models\PickupDrop::directions()[$direction] ?? $direction }}
                            </flux:badge>
                        </div>
                        <flux:description>Derived from the type above.</flux:description>
                    </flux:field>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:date-picker wire:model="scheduled_date" label="Entry Date" placeholder="Select date" required with-today selectable-header fixed-weeks type="input" />
                    <flux:time-picker wire:model="scheduled_time" label="Entry Time" placeholder="Select time" required type="input" />
                    <flux:select wire:model="time_slot_id" variant="listbox" label="Time Slot" placeholder="Pick a slot" required>
                        @foreach ($this->timeSlots as $slot)
                            <flux:select.option :value="$slot->id" wire:key="slot-{{ $slot->id }}">{{ $slot->window() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Status is derived: each button records the fact that moves
                         it, available once the previous fact exists. --}}
                    @if ($editingId && ! $cancelled_at && $status !== $PD::STATUS_COMPLETED)
                        <flux:field>
                            <flux:label>Driver Progress</flux:label>
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:button size="sm" variant="{{ $status === $PD::STATUS_DRIVER_ASSIGNED ? 'primary' : 'ghost' }}"
                                    :disabled="$status !== $PD::STATUS_DRIVER_ASSIGNED"
                                    wire:click="markDeparted">Departed</flux:button>
                                <flux:button size="sm" variant="{{ $status === $PD::STATUS_DRIVER_ON_THE_WAY ? 'primary' : 'ghost' }}"
                                    :disabled="$status !== $PD::STATUS_DRIVER_ON_THE_WAY"
                                    wire:click="markReached">Reached location</flux:button>
                                <flux:button size="sm" variant="ghost"
                                    :disabled="$status !== $PD::STATUS_DRIVER_ON_THE_WAY"
                                    wire:click="confirmHandover">
                                    {{ $direction === $PD::DIRECTION_PICKUP ? 'Vehicle collected' : 'Vehicle delivered' }}
                                </flux:button>
                            </div>
                            <flux:description>
                                @if ($status === $PD::STATUS_PENDING) Assign a driver or vendor below to begin.
                                @else Collection is also confirmed automatically by OTP or condition photos.
                                @endif
                            </flux:description>
                        </flux:field>
                    @endif

                    @if ($status === $PD::STATUS_PENDING)
                        <flux:select wire:model="pending_reason_id" variant="combobox" clearable label="Pending Reason">
                            <x-slot name="input">
                                <flux:select.input wire:model="pendingReasonSearch" placeholder="Pick or type to add…" />
                            </x-slot>
                            @foreach ($this->pendingReasons as $r)
                                <flux:select.option :value="$r->id" wire:key="pnd-{{ $r->id }}">{{ $r->name }}</flux:select.option>
                            @endforeach
                            @can('pending_reason_master.create')
                                <flux:select.option.create wire:click="createPendingReason" min-length="2">
                                    Create "<span wire:text="pendingReasonSearch"></span>"
                                </flux:select.option.create>
                            @endcan
                        </flux:select>
                    @endif

                    <flux:select wire:model="reschedule_reason_id" variant="combobox" clearable label="Reschedule Reason">
                        <x-slot name="input">
                            <flux:select.input wire:model="rescheduleReasonSearch" placeholder="Only if moved — pick or type to add…" />
                        </x-slot>
                        @foreach ($this->pendingReasons as $r)
                            <flux:select.option :value="$r->id" wire:key="rsc-{{ $r->id }}">{{ $r->name }}</flux:select.option>
                        @endforeach
                        @can('pending_reason_master.create')
                            <flux:select.option.create wire:click="createRescheduleReason" min-length="2">
                                Create "<span wire:text="rescheduleReasonSearch"></span>"
                            </flux:select.option.create>
                        @endcan
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- CUSTOMER & VEHICLE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Customer & Vehicle</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Pick the customer first — the vehicle list filters to them.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:field>
                    <flux:label>Customer</flux:label>
                    <div class="flex items-stretch gap-2">
                        <div class="flex-1 min-w-0">
                            <flux:select wire:model.live="customer_id" variant="listbox" searchable required placeholder="Pick a customer…" :filter="false">
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
                                <flux:button icon="plus" variant="ghost" type="button" x-on:click="$flux.modal('customer-quick-add').show()" />
                            </flux:tooltip>
                        @endcan
                    </div>
                    <flux:error name="customer_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Vehicle</flux:label>
                    <div class="flex items-stretch gap-2">
                        <div class="flex-1 min-w-0">
                            <flux:select wire:model.live="customer_vehicle_id" variant="listbox" searchable clearable required :filter="false" placeholder="Search a vehicle (owner auto-fills)…">
                                <x-slot name="search">
                                    <flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Registration no…" />
                                </x-slot>
                                @foreach ($this->customerVehicles as $v)
                                    <flux:select.option :value="$v['id']" wire:key="veh-{{ $v['id'] }}">{{ $v['label'] }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                        @can('customer_vehicle_master.create')
                            <flux:tooltip content="Quick add a new vehicle (creates the customer too)">
                                <flux:button icon="plus" variant="ghost" type="button" x-on:click="$flux.modal('customer-vehicle-quick-add').show()" />
                            </flux:tooltip>
                        @endcan
                    </div>
                    <flux:error name="customer_vehicle_id" />
                </flux:field>
            </div>
        </section>

        <flux:separator />

        {{-- ADDRESSES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Addresses</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Where we collect from and where we return to. Distance sets the charge.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                {{-- The type decides which addresses exist: pickup-only asks where
                     to collect, drop-only where to return, both asks for both. --}}
                @if (! $this->optionInvolvesPickup() && ! $this->optionInvolvesDrop())
                    <flux:callout variant="secondary" icon="information-circle" inline>
                        <flux:callout.text>Pick a Pickup/Drop Type above to capture addresses.</flux:callout.text>
                    </flux:callout>
                @endif

                @if ($this->optionInvolvesPickup())
                    @if ($this->customerAddresses->isNotEmpty())
                        <flux:select wire:model.live="address_choice" variant="listbox" label="Pickup — saved address">
                            @foreach ($this->customerAddresses as $addr)
                                <flux:select.option :value="(string) $addr['id']" wire:key="paddr-{{ $addr['id'] }}">
                                    {{ $addr['label'] ?? 'Address' }}{{ $addr['is_primary'] ? ' (Primary)' : '' }} — {{ \Illuminate\Support\Str::limit($addr['full'], 60) }}
                                </flux:select.option>
                            @endforeach
                            <flux:select.option value="custom">Other / type a new address…</flux:select.option>
                        </flux:select>
                    @endif

                    <flux:textarea wire:model="pickup_address" label="Pickup Address" placeholder="House / street / area / pincode" rows="2" required />
                    <flux:error name="pickup_address" />

                    @include('partials.region-pickers', ['leg' => 'pickup'])
                @endif

                @if ($this->optionInvolvesDrop())
                    <flux:separator variant="subtle" />

                    @if ($this->customerAddresses->isNotEmpty())
                        <flux:select wire:model.live="drop_address_choice" variant="listbox" label="Drop — saved address">
                            @foreach ($this->customerAddresses as $addr)
                                <flux:select.option :value="(string) $addr['id']" wire:key="daddr-{{ $addr['id'] }}">
                                    {{ $addr['label'] ?? 'Address' }}{{ $addr['is_primary'] ? ' (Primary)' : '' }} — {{ \Illuminate\Support\Str::limit($addr['full'], 60) }}
                                </flux:select.option>
                            @endforeach
                            <flux:select.option value="custom">Other / type a new address…</flux:select.option>
                        </flux:select>
                    @endif

                    <flux:textarea wire:model="drop_address" label="Drop Address" placeholder="Where the vehicle should be returned" rows="2" required />
                    <flux:error name="drop_address" />

                    @include('partials.region-pickers', ['leg' => 'drop'])
                @endif

                <flux:field>
                    <flux:label>Contact Phone</flux:label>
                    <flux:input.group>
                        <flux:input.group.prefix>+91</flux:input.group.prefix>
                        <flux:input wire:model="contact_phone" mask="99999 99999" placeholder="98765 43210" inputmode="numeric" />
                    </flux:input.group>
                    <flux:error name="contact_phone" />
                </flux:field>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input type="number" step="0.01" wire:model.live.debounce.500ms="distance_km" label="Distance (KM)" placeholder="12" min="0" />

                    <flux:select wire:model="distance_slab_id" variant="listbox" clearable label="Distance Slab" placeholder="Auto from distance">
                        @foreach ($this->distanceSlabs as $slab)
                            <flux:select.option :value="$slab->id" wire:key="slab-{{ $slab->id }}">{{ $slab->band() }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:field>
                        <flux:label>Charge</flux:label>
                        <flux:input.group>
                            <flux:input.group.prefix>₹</flux:input.group.prefix>
                            <flux:input wire:model="distance_charge" inputmode="decimal" placeholder="200.00" />
                        </flux:input.group>
                        <flux:description>Filled from the slab; override if needed.</flux:description>
                        <flux:error name="distance_charge" />
                    </flux:field>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ASSIGNMENT & ROUTING --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Assignment & Routing</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">An in-house driver <em>or</em> a vendor courier — not both.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="driver_employee_id" variant="listbox" searchable clearable label="Driver" placeholder="In-house driver…">
                        @foreach ($this->drivers as $d)
                            <flux:select.option :value="$d->id" wire:key="drv-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="vendor_courier_id" variant="listbox" searchable clearable label="Vendor / Courier" placeholder="Outsourced…">
                        @foreach ($this->couriers as $c)
                            <flux:select.option :value="$c->id" wire:key="cour-{{ $c->id }}">{{ $c->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:error name="driver_employee_id" />
                <flux:error name="vendor_courier_id" />

                {{-- Dependency order: the department decides which service types
                     and which advisors exist at all, so it is asked first. --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select wire:model.live="workshop_department_id" variant="listbox" searchable label="Department" placeholder="Department…" required>
                        @foreach ($this->departments as $dep)
                            <flux:select.option :value="$dep->id" wire:key="dep-{{ $dep->id }}">{{ $dep->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="service_type_id" variant="listbox" searchable clearable required
                        label="Service Type"
                        :placeholder="$workshop_department_id ? 'Service type…' : 'Pick a department first'"
                        :disabled="! $workshop_department_id">
                        @foreach ($this->serviceTypes as $st)
                            <flux:select.option :value="$st->id" wire:key="st-{{ $st->id }}">{{ $st->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="advisor_employee_id" variant="listbox" searchable clearable required
                        label="Advisor"
                        :placeholder="$workshop_department_id ? 'Service advisor…' : 'Pick a department first'"
                        :disabled="! $workshop_department_id">
                        @foreach ($this->advisors as $a)
                            <flux:select.option :value="$a->id" wire:key="adv-{{ $a->id }}">{{ $a->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- COMPLAINTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Customer Complaints</flux:heading>
                <flux:error name="complaints" />
                <flux:text size="sm" class="mt-1 text-zinc-500">Tick the usual jobs for this department, then add anything the customer reported in their own words.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                @include('partials.quick-services')

                {{-- Direct complaint entry — the customer's words, no dropdowns. --}}
                @forelse ($complaints as $i => $complaint)
                    <div class="flex items-end gap-2" wire:key="complaint-{{ $i }}">
                        <div class="flex-1 min-w-0">
                            <flux:input
                                wire:model="complaints.{{ $i }}.description"
                                placeholder="e.g. SUSPENSION NOISE OVER SPEED BREAKERS"
                                required
                            />
                            <flux:error name="complaints.{{ $i }}.description" />
                        </div>
                        <flux:button type="button" variant="ghost" icon="trash" wire:click="removeComplaint({{ $i }})" />
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

        {{-- DOCUMENT CHECKLIST --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Document Checklist</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Picking a template copies its lines here — later template edits won't rewrite them.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <flux:select wire:model.live="checklist_template_id" variant="listbox" clearable label="Checklist Template" placeholder="Load a template…">
                    @foreach ($this->checklistTemplates as $t)
                        <flux:select.option :value="$t->id" wire:key="tpl-{{ $t->id }}">{{ $t->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                @forelse ($documents as $i => $doc)
                    <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_auto_auto] gap-3 items-center" wire:key="pdd-{{ $i }}">
                        <flux:input wire:model="documents.{{ $i }}.label" placeholder="Document name" required />
                        <flux:checkbox wire:model="documents.{{ $i }}.is_required" label="Required" />
                        <flux:checkbox wire:model="documents.{{ $i }}.is_collected" label="Collected" />
                        <flux:button type="button" variant="ghost" icon="trash" wire:click="removeDocument({{ $i }})" />
                    </div>
                @empty
                    <flux:text size="sm" class="text-zinc-500">No documents listed yet.</flux:text>
                @endforelse

                <flux:button type="button" variant="ghost" icon="plus" size="sm" wire:click="addDocument">Add document</flux:button>
            </div>
        </section>

        <flux:separator />

        {{-- VEHICLE CONDITION PHOTOS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Vehicle Condition</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Photo evidence at collection and again at handover.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                @forelse ($photos as $i => $photo)
                    <div class="grid grid-cols-1 md:grid-cols-[1fr_150px_1fr_auto] gap-3 items-end" wire:key="pdp-{{ $i }}">
                        <flux:select wire:model="photos.{{ $i }}.photo_type_id" variant="listbox" searchable clearable label="View" placeholder="Odometer, Front…">
                            @foreach ($this->photoTypes as $pt)
                                <flux:select.option :value="$pt->id" wire:key="pt-{{ $i }}-{{ $pt->id }}">{{ $pt->name }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="photos.{{ $i }}.leg" variant="listbox" label="When">
                            @foreach ($PD::legs() as $key => $label)
                                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <div>
                            <flux:input type="file" wire:model="photoFiles.{{ $i }}" label="Image" accept="image/*" />
                            @if ($photo['path'])
                                <flux:text size="xs" class="text-zinc-500 mt-1">Saved: {{ basename($photo['path']) }}</flux:text>
                            @endif
                            <flux:error name="photoFiles.{{ $i }}" />
                        </div>

                        <flux:button type="button" variant="ghost" icon="trash" wire:click="removePhoto({{ $i }})" />
                    </div>
                @empty
                    <flux:text size="sm" class="text-zinc-500">No condition photos yet.</flux:text>
                @endforelse

                <flux:button type="button" variant="ghost" icon="plus" size="sm" wire:click="addPhoto">Add photo</flux:button>
            </div>
        </section>

        <flux:separator />

        {{-- HANDOVER & NOTES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Handover & Notes</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">OTP confirms the vehicle changed hands with the right person.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select wire:model="otp_mode" variant="listbox" label="OTP Verification" required>
                        @foreach ($PD::otpModes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="pickup_otp" label="Pickup OTP" placeholder="123456" maxlength="10" class:input="font-mono tracking-widest" />
                    <flux:input wire:model="delivery_otp" label="Delivery OTP" placeholder="123456" maxlength="10" class:input="font-mono tracking-widest" />
                </div>

                <flux:textarea wire:model="notes" label="Driver Notes" placeholder="Apartment 3B; ask for Suresh; gate closes at 8 PM." rows="3" />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('pickup-drop.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save Changes' : 'Schedule Pickup/Drop' }}</flux:button>
        </div>
    </form>

    <flux:modal name="cancel-pickup-drop" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Cancel this job?</flux:heading>
                <flux:subheading>The record stays on file and can be restored.</flux:subheading>
            </div>

            <flux:select wire:model="cancel_reason_id" variant="listbox" label="Cancel Reason" placeholder="Why is it being cancelled?">
                @foreach ($this->cancelReasons as $r)
                    <flux:select.option :value="$r->id" wire:key="cxl-{{ $r->id }}">{{ $r->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="cancel_reason_id" />

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Keep it</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="cancelPickupDrop">Cancel Job</flux:button>
            </div>
        </div>
    </flux:modal>

    @include('customer-master::_quick_add_modal')
    @include('partials.quick-add-customer-vehicle-modal')
</div>
