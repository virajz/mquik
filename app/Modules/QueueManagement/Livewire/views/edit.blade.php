@php($SQ = \App\Modules\QueueManagement\Models\ServiceQueue::class)
<div>
    <form wire:submit="save" class="max-w-3xl">
        <div class="mb-8">
            <flux:link :href="route('queue-management.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Queue Management
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($queue_no ?: 'Edit Queue Entry') : 'Add to Queue' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Add a vehicle to a service queue and track its timing.</flux:text>
        </div>

        <flux:separator />

        {{-- VEHICLE & SERVICE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Vehicle & Service</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">What's queued and for which service.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="queue_type" variant="listbox" label="Queue Type" required autofocus>
                        @foreach ($SQ::queueTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="labour_id" variant="listbox" searchable clearable label="Service (Labour)" placeholder="Wash / Alignment / …">
                        @foreach ($this->labours as $l)
                            <flux:select.option :value="$l->id" wire:key="lab-{{ $l->id }}">{{ $l->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="customer_vehicle_id" variant="listbox" searchable clearable :filter="false" label="Vehicle" placeholder="Registration no…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Search reg no…" /></x-slot>
                        @foreach ($this->vehicles as $veh)
                            <flux:select.option :value="$veh->id" wire:key="vh-{{ $veh->id }}">{{ $veh->registration_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="Optional…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                        @foreach ($this->jobCards as $jc)
                            <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:input wire:model="job_description" label="Job Description" placeholder="e.g. FULL BODY WASH + INTERIOR" />
                <flux:select wire:model="technician_id" variant="listbox" searchable clearable label="Technician" placeholder="Assigned technician" class="md:max-w-sm">
                    @foreach ($this->employees as $e)
                        <flux:select.option :value="$e->id" wire:key="tech-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </section>

        <flux:separator />

        {{-- ORDERING & PRIORITY --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Ordering & Priority</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">FIFO or priority; high-priority needs approval.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="ordering_mode" variant="listbox" label="Ordering" required>
                        @foreach ($SQ::orderingModes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="screen_view" variant="listbox" label="Screen View" required>
                        @foreach ($SQ::screenViews() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:switch wire:model.live="is_high_priority" label="High Priority" description="Jumps the queue — requires an approval reason." />

                <div x-show="$wire.is_high_priority" x-cloak class="space-y-3">
                    <flux:select wire:model="high_priority_reason" variant="listbox" clearable label="High Priority Reason" placeholder="Why prioritised">
                        @foreach ($SQ::highPriorityReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="high_priority_reason" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <flux:select wire:model="hp_requested_by_id" variant="listbox" searchable clearable label="Approval Requested By">
                            @foreach ($this->employees as $e)
                                <flux:select.option :value="$e->id" wire:key="hpb-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model="hp_requested_to_id" variant="listbox" searchable clearable label="Approval Requested To">
                            @foreach ($this->employees as $e)
                                <flux:select.option :value="$e->id" wire:key="hpt-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- TIMING & STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Timing & Status</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Timestamps drive waiting / service / TAT.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="grid grid-cols-2 gap-2">
                        <flux:date-picker wire:model="promised_delivery_at" label="Promised Delivery" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                        <flux:time-picker wire:model="promised_delivery_at_time" label="Time" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <flux:date-picker wire:model="expected_completion_at" label="Expected Completion" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                        <flux:time-picker wire:model="expected_completion_at_time" label="Time" />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="grid grid-cols-2 gap-2">
                        <flux:date-picker wire:model="kept_at" label="Kept for Service" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                        <flux:time-picker wire:model="kept_at_time" label="Time" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <flux:date-picker wire:model="work_started_at" label="Work Start" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                        <flux:time-picker wire:model="work_started_at_time" label="Time" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <flux:date-picker wire:model="work_ended_at" label="Work End" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                        <flux:time-picker wire:model="work_ended_at_time" label="Time" />
                    </div>
                </div>
                <flux:error name="work_ended_at" />

                <flux:select wire:model.live="status" variant="listbox" label="Status" required class="md:max-w-xs">
                    @foreach ($SQ::statuses() as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div x-show="$wire.status === 'on_hold'" x-cloak>
                    <flux:select wire:model="pause_reason" variant="listbox" clearable label="Pause Reason" placeholder="Why on hold" class="md:max-w-sm">
                        @foreach ($SQ::pauseReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="pause_reason" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="delay_reason" variant="listbox" clearable label="Delay Reason" placeholder="If delayed…">
                        @foreach ($SQ::delayReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="rework_reason" variant="listbox" clearable label="Rework Reason" placeholder="If reworked…">
                        @foreach ($SQ::reworkReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="notes" label="Notes" placeholder="Optional remarks." rows="2" />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('queue-management.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Add to queue' }}</flux:button>
        </div>
    </form>
</div>
