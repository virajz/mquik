@php($G = \App\Modules\GateInOut\Models\GateInOut::class)
@php($MOVE = \App\Modules\GateInOut\Models\GateVisitMovement::class)
<div>
    <form wire:submit="save" novalidate class="max-w-3xl">
        <div class="mb-8 flex items-start justify-between gap-4">
            <div>
                <flux:link :href="route('gate-in-out.index')" variant="ghost" class="text-xs">
                    <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Inward / Outward
                </flux:link>
                <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? 'Edit Visit' : 'Record Inward' }}</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">One record covers the whole visit. Fill the outward half when the vehicle leaves — TAT is calculated from the two.</flux:text>
            </div>
            @if ($editingId)
                <div class="flex shrink-0 items-center gap-2">
                    @if ($this->linkedJobCards->isNotEmpty())
                        <flux:dropdown align="end">
                            <flux:button size="sm" variant="ghost" icon="clipboard-document-list" icon:trailing="chevron-down">
                                Job Cards ({{ $this->linkedJobCards->count() }})
                            </flux:button>
                            <flux:menu>
                                @foreach ($this->linkedJobCards as $card)
                                    <flux:menu.item :href="route('job-card.edit', $card->id)" wire:navigate>
                                        {{ $card->job_card_no }} · {{ \App\Modules\JobCard\Models\JobCard::statuses()[$card->status] ?? $card->status }}
                                    </flux:menu.item>
                                @endforeach
                            </flux:menu>
                        </flux:dropdown>
                    @endif
                    @can('job_card.create')
                        {{-- Still offered when cards exist: one arrival can need more than one. --}}
                        <flux:button :href="route('job-card.create', ['from-gate-event' => $editingId])" wire:navigate size="sm" variant="primary" icon="plus">
                            New Job Card
                        </flux:button>
                    @endcan
                </div>
            @endif
        </div>

        <flux:separator />

        {{-- INWARD --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Inward</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">When the vehicle arrived and where it's parked.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                {{-- Fixed: the arrival moment is stamped when the record is made
                     and is not editable afterwards. --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Entry Date</flux:label>
                        <flux:input :value="\Illuminate\Support\Carbon::parse($entered_date)->format('d/m/Y')" readonly class:input="text-zinc-500" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Entry Time</flux:label>
                        <flux:input :value="$entered_time" readonly class:input="text-zinc-500 font-mono" />
                        <flux:description>Recorded automatically — cannot be changed.</flux:description>
                    </flux:field>
                </div>

                {{-- Pick the vehicle we already know; typing the plate every time
                     is slow and is how duplicate walk-ins get created. --}}
                <flux:field>
                    <flux:label>Vehicle</flux:label>
                    <div class="flex items-stretch gap-2">
                        <div class="min-w-0 flex-1">
                <flux:select wire:model.live="customer_vehicle_id" variant="listbox" searchable clearable :filter="false"
                    placeholder="Search registration, customer or phone…">
                    <x-slot name="search">
                        <flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Reg no, customer name or phone…" />
                    </x-slot>
                    @foreach ($this->vehicleOptions as $v)
                        <flux:select.option :value="$v->id" wire:key="veh-{{ $v->id }}">
                            {{ $v->registration_no }}
                            @if ($v->model)· {{ trim(($v->model->brand->name ?? '').' '.$v->model->name) }}@endif
                            @if ($v->customer)· {{ trim($v->customer->first_name.' '.$v->customer->last_name) }}@endif
                        </flux:select.option>
                    @endforeach
                </flux:select>
                        </div>
                        @can('customer_vehicle_master.create')
                            <flux:tooltip content="Car not in the system? Add it here">
                                <flux:button type="button" icon="plus" variant="ghost"
                                    x-on:click="$flux.modal('customer-vehicle-quick-add').show()" />
                            </flux:tooltip>
                        @endcan
                    </div>
                </flux:field>

                <flux:input
                    wire:model.live.debounce.500ms="registration_no"
                    label="Registration Number"
                    placeholder="GJ 05 AA 1234"
                    description="Filled from the picker above. Type it only for a vehicle we have never seen."
                    class:input="font-mono uppercase tracking-wider"
                    autofocus
                    required
                />

                @if ($customer_vehicle_id)
                    <div class="rounded-md border border-lime-200 dark:border-lime-900 bg-lime-50 dark:bg-lime-900/20 px-3 py-2 text-sm flex items-center gap-2">
                        <flux:icon.check-circle class="size-4 text-lime-600 dark:text-lime-400" />
                        <span>{{ $this->linkedVehicleName ?: 'Known customer vehicle' }} — linked (#{{ $customer_vehicle_id }}).</span>
                    </div>
                @elseif (trim($registration_no))
                    <div class="rounded-md border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-900/20 px-3 py-2 text-sm flex items-center gap-2">
                        <flux:icon.exclamation-triangle class="size-4 text-amber-600 dark:text-amber-400" />
                        <span>No matching customer vehicle — recording as walk-in.</span>
                    </div>
                @endif

                {{-- Photo of the car at the barrier — proof of condition on arrival. --}}
                <flux:field>
                    <flux:label>Gate Photo</flux:label>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <flux:input type="file" wire:model="capturedImage" accept="image/*" capture="environment" />
                        </div>
                        @if ($captured_image_path)
                            <flux:link :href="\Illuminate\Support\Facades\Storage::disk('public')->url($captured_image_path)" target="_blank" class="text-xs shrink-0">View saved</flux:link>
                        @endif
                    </div>
                    <flux:error name="capturedImage" />
                </flux:field>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="entry_gate_id" variant="listbox" clearable label="Entry Gate" placeholder="Which gate…" required>
                        @foreach ($this->gates as $g)
                            <flux:select.option :value="$g->id" wire:key="eg-{{ $g->id }}">{{ $g->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="parking_slot_id" variant="listbox" clearable label="Parking Slot" placeholder="Where parked…">
                        @foreach ($this->parkingSlots as $s)
                            <flux:select.option :value="$s->id" wire:key="ps-{{ $s->id }}">{{ $s->name }}</flux:select.option>
                        @endforeach
                    </flux:select>


                </div>
            </div>
        </section>

        <flux:separator />

        {{-- TRIPS WHILE ON SITE --}}
        @if ($editingId)
            <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
                <div>
                    <flux:heading size="lg">Movements</flux:heading>
                    <flux:text size="sm" class="mt-1 text-zinc-500">
                        Trips out and back before delivery — trial run, outside labour, fuel. These are not the outward;
                        the vehicle is still in our care.
                    </flux:text>
                    @if ($this->isOffSite)
                        <flux:badge color="amber" size="sm" icon="arrow-right-start-on-rectangle" class="mt-3">Off site now</flux:badge>
                    @endif
                </div>
                <div class="space-y-3 min-w-0">
                    <div class="flex justify-end">
                        <flux:modal.trigger name="send-out">
                            <flux:button type="button" size="sm" variant="primary" icon="arrow-right-start-on-rectangle"
                                :disabled="$this->isOffSite">
                                Send out
                            </flux:button>
                        </flux:modal.trigger>
                    </div>

                    @forelse ($this->movements as $m)
                        <div wire:key="mv-{{ $m->id }}"
                            class="rounded-md border p-3 {{ $m->isOverdue() ? 'border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/30' : 'border-zinc-200 dark:border-zinc-800' }}">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-medium">{{ $MOVE::purposes()[$m->purpose] ?? $m->purpose }}</span>
                                        @if ($m->isOut())
                                            <flux:badge size="sm" :color="$m->isOverdue() ? 'red' : 'amber'">
                                                {{ $m->isOverdue() ? 'Overdue · '.$m->overdueLabel() : 'Out '.$m->elapsedLabel() }}
                                            </flux:badge>
                                        @else
                                            <flux:badge size="sm" color="lime">Away {{ $m->elapsedLabel() }}</flux:badge>
                                        @endif
                                        @if ($m->jobCard)<flux:badge size="sm" color="zinc">{{ $m->jobCard->job_card_no }}</flux:badge>@endif
                                    </div>
                                    <flux:text size="sm" class="mt-0.5 text-zinc-500">
                                        Out {{ $m->out_at?->format('d/m, h:i A') }}
                                        @if ($m->in_at) · back {{ $m->in_at->format('d/m, h:i A') }}
                                        @elseif ($m->expected_back_at) · due {{ $m->expected_back_at->format('d/m, h:i A') }}
                                        @endif
                                        @if ($m->vendor) · {{ $m->vendor->name }} @endif
                                        @if ($m->driver) · {{ $m->driver->name }} @endif
                                        @if ($m->distanceKm() !== null) · {{ number_format($m->distanceKm()) }} km @endif
                                    </flux:text>
                                </div>
                                @if ($m->isOut())
                                    <flux:button type="button" size="xs" variant="primary" icon="arrow-left-end-on-rectangle"
                                        wire:click="bringBack({{ $m->id }})">
                                        Back in
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                            The vehicle has not left the premises since it came in.
                        </div>
                    @endforelse
                </div>
            </section>

            <flux:separator />
        @endif

        {{-- OUTWARD --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Final Delivery</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">The departure that closes the visit. Leave blank while the vehicle is still ours — trips out and back belong above.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                {{-- Stamped by "Mark delivered", never typed — backdating a
                     departure is what a gate register exists to prevent. --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Exit Date</flux:label>
                        <flux:input :value="$exited_date ? \Illuminate\Support\Carbon::parse($exited_date)->format('d/m/Y') : '— not yet —'" readonly class:input="text-zinc-500" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Exit Time</flux:label>
                        <flux:input :value="$exited_time ?? '— not yet —'" readonly class:input="text-zinc-500 font-mono" />
                        <flux:description>Stamped when the guard marks the vehicle delivered.</flux:description>
                    </flux:field>
                </div>

                {{-- Two columns, matching the rows above and below: dropping the
                     outward-type picker left this as 2 fields in a 3-column grid,
                     so they sat narrow and out of line with everything else. --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="exit_gate_id" variant="listbox" clearable label="Exit Gate" placeholder="Which gate…" required>
                        @foreach ($this->gates as $g)
                            <flux:select.option :value="$g->id" wire:key="xg-{{ $g->id }}">{{ $g->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="driver_type" variant="listbox" clearable label="Driver Type" placeholder="Who is driving…" required>
                        @foreach ($G::driverTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
<flux:select wire:model="delivered_by_id" variant="listbox" searchable clearable label="Delivered By" placeholder="Advisor or cashier…" required>
                        @foreach ($this->deliveryStaff as $e)
                            <flux:select.option :value="$e->id" wire:key="dlv-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

<flux:select wire:model="exit_by_id" variant="listbox" searchable clearable label="Exit By (Security Guard)" placeholder="Which guard let it out…" required>
                        @foreach ($this->securityGuards as $e)
                            <flux:select.option :value="$e->id" wire:key="grd-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                @if ($editingId && ! $exited_date && $status !== $G::STATUS_CANCELLED)
                    <flux:button variant="primary" icon="check-badge" wire:click="markDelivered">
                        Mark delivered — stamp exit now
                    </flux:button>
                @endif
            </div>
        </section>

        <flux:separator />

        {{-- STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Status & Notes</flux:heading>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    {{-- Derived: pending while the car is ours, completed once the
                         exit stamp lands. Cancelling stays a deliberate act. --}}
                    <flux:field>
                        <flux:label>Job Status</flux:label>
                        <div class="flex items-center gap-2 h-10">
                            <flux:badge :color="match ($status) {
                                'pending' => 'amber', 'completed' => 'green', 'cancelled' => 'zinc', default => 'zinc',
                            }" size="sm">{{ $G::statuses()[$status] ?? $status }}</flux:badge>
                            @if ($editingId && $status === $G::STATUS_CANCELLED)
                                <flux:button size="xs" variant="ghost" wire:click="restoreVisit">Restore</flux:button>
                            @elseif ($editingId && $status !== $G::STATUS_COMPLETED)
                                <flux:button size="xs" variant="ghost" wire:click="cancelVisit">Cancel visit</flux:button>
                            @endif
                        </div>
                        <flux:description>Updates itself from the delivery stamp.</flux:description>
                    </flux:field>

                    <flux:select wire:model="source" variant="listbox" label="Source">
                        @foreach ($G::sources() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="notes" label="Notes" placeholder="Optional — e.g. 'driver waiting outside', 'late entry'." rows="2" />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('gate-in-out.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Record visit' }}</flux:button>
        </div>
    </form>

    {{-- SEND OUT --}}
    @if ($editingId)
        <flux:modal name="send-out" class="md:w-lg">
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">Send vehicle out</flux:heading>
                    <flux:text size="sm" class="mt-1 text-zinc-500">
                        Logged as a trip, not an outward — the visit stays open until delivery.
                    </flux:text>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="tripPurpose" variant="listbox" label="Purpose" required>
                        @foreach ($MOVE::purposes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="tripJobCardId" variant="listbox" searchable clearable label="Job Card" placeholder="Which job…">
                        @foreach ($this->linkedJobCards as $card)
                            <flux:select.option :value="$card->id" wire:key="tj-{{ $card->id }}">{{ $card->job_card_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                @if ($tripPurpose === 'outside_labour')
                    <flux:select wire:model="tripVendorId" variant="listbox" searchable clearable label="Vendor" placeholder="Where is it going…">
                        @foreach ($this->vendors as $v)
                            <flux:select.option :value="$v->id" wire:key="tv-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="tripDriverId" variant="listbox" searchable clearable label="Driver" placeholder="Who is taking it…">
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="td-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="tripOdometerOut" type="number" min="0" label="Odometer Out" placeholder="km" class:input="text-right font-mono" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:date-picker locale="en-IN" wire:model="tripExpectedBackDate" label="Expected Back — Date"
                        placeholder="When is it due" with-today selectable-header fixed-weeks type="input" clearable />
                    <flux:time-picker wire:model="tripExpectedBackTime" label="Expected Back — Time"
                        placeholder="Optional" type="input" clearable />
                </div>

                <flux:textarea wire:model="tripNotes" rows="2" label="Notes" placeholder="Optional" />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" icon="arrow-right-start-on-rectangle" wire:click="sendOut">Send out</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- @include, not <x-…>: a Blade component gets its own scope and would
         not see the component's $quickCV state. --}}
    @include('partials.quick-add-customer-vehicle-modal')
</div>
