@php($G = \App\Modules\GateInOut\Models\GateInOut::class)
<div>
    <form wire:submit="save" class="max-w-3xl">
        <div class="mb-8">
            <flux:link :href="route('gate-in-out.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Inward / Outward
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? 'Edit Visit' : 'Record Inward' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">One record covers the whole visit. Fill the outward half when the vehicle leaves — TAT is calculated from the two.</flux:text>
        </div>

        <flux:separator />

        {{-- INWARD --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Inward</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">When the vehicle arrived and where it's parked.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:date-picker wire:model="entered_date" label="Entry Date" placeholder="Today" with-today selectable-header fixed-weeks type="input" />
                    <flux:time-picker wire:model="entered_time" label="Entry Time" placeholder="Now" type="input" />
                </div>

                <flux:input
                    wire:model.live.debounce.500ms="registration_no"
                    label="Registration Number"
                    placeholder="GJ 05 AA 1234"
                    description="Type or paste the reg-no — we try to auto-link to a customer vehicle."
                    class:input="font-mono uppercase tracking-wider"
                    autofocus
                    required
                />

                @if ($customer_vehicle_id)
                    <div class="rounded-md border border-lime-200 dark:border-lime-900 bg-lime-50 dark:bg-lime-900/20 px-3 py-2 text-sm flex items-center gap-2">
                        <flux:icon.check-circle class="size-4 text-lime-600 dark:text-lime-400" />
                        <span>Linked to a known customer vehicle (#{{ $customer_vehicle_id }}).</span>
                    </div>
                @elseif (trim($registration_no))
                    <div class="rounded-md border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-900/20 px-3 py-2 text-sm flex items-center gap-2">
                        <flux:icon.exclamation-triangle class="size-4 text-amber-600 dark:text-amber-400" />
                        <span>No matching customer vehicle — recording as walk-in.</span>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="entry_gate_id" variant="listbox" clearable label="Entry Gate" placeholder="Which gate…">
                        @foreach ($this->gates as $g)
                            <flux:select.option :value="$g->id" wire:key="eg-{{ $g->id }}">{{ $g->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="parking_slot_id" variant="listbox" clearable label="Parking Slot" placeholder="Where parked…">
                        @foreach ($this->parkingSlots as $s)
                            <flux:select.option :value="$s->id" wire:key="ps-{{ $s->id }}">{{ $s->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable label="Job Card" placeholder="Link a job card…">
                        @foreach ($this->jobCards as $jc)
                            <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- OUTWARD --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Outward</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Leave blank while the vehicle is still on site.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:date-picker wire:model="exited_date" label="Exit Date" placeholder="Not yet" with-today selectable-header fixed-weeks type="input" clearable />
                    <flux:time-picker wire:model="exited_time" label="Exit Time" placeholder="Not yet" type="input" clearable />
                </div>
                <flux:error name="exited_date" />
                <flux:error name="exited_time" />

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="exit_gate_id" variant="listbox" clearable label="Exit Gate" placeholder="Which gate…">
                        @foreach ($this->gates as $g)
                            <flux:select.option :value="$g->id" wire:key="xg-{{ $g->id }}">{{ $g->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="outward_type" variant="listbox" clearable label="Outward Type" placeholder="Why leaving…">
                        @foreach ($G::outwardTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="driver_type" variant="listbox" clearable label="Driver Type" placeholder="Who is driving…">
                        @foreach ($G::driverTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="delivered_by_id" variant="listbox" searchable clearable label="Delivered By" placeholder="Driver or advisor…">
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="db-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="exit_by_id" variant="listbox" searchable clearable label="Exit Approved By" placeholder="Security guard…">
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="xb-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
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
                    <flux:select wire:model="status" variant="listbox" label="Job Status" required>
                        @foreach ($G::statuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

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
</div>
