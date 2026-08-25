@php($VM = \App\Modules\VehicleMovement\Models\VehicleMovement::class)
@php($ATT = \App\Modules\VehicleMovement\Models\VehicleMovementAttachment::class)
<div>
    <form wire:submit="save" novalidate class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('vehicle-movement.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Vehicle Inward / Outward
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($movement_no ?: 'Edit Movement') : 'Log Vehicle Movement' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Record a vehicle entering or leaving the premises.</flux:text>
        </div>

        <flux:separator />

        {{-- MOVEMENT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Movement</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Direction, vehicle and slot.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model.live="movement_type" variant="listbox" label="Movement Type" required autofocus>
                        @foreach ($VM::movementTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model.live="customer_vehicle_id" variant="listbox" searchable clearable :filter="false" label="Vehicle" placeholder="Registration…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Search vehicle…" /></x-slot>
                        @foreach ($this->vehicles as $vh)<flux:select.option :value="$vh->id" wire:key="vh-{{ $vh->id }}">{{ $vh->registration_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input wire:model="number_plate" label="Number Plate" placeholder="From plate reading" class:input="font-mono uppercase" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="parking_slot" variant="listbox" clearable label="Parking Slot" placeholder="Slot…">
                        @foreach ($VM::parkingSlots() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="gate" variant="listbox" clearable label="Gate" placeholder="Gate…">
                        @foreach ($VM::gates() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="job_status" variant="listbox" label="Job Status" required>
                        @foreach ($VM::jobStatuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>

                {{-- Outward-only --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3" x-show="$wire.movement_type === 'outward'" x-cloak>
                    <div>
                        <flux:select wire:model="outward_type" variant="listbox" clearable label="Outward Purpose" placeholder="Trial / Delivery…">
                            @foreach ($VM::outwardTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:error name="outward_type" />
                    </div>
                    <flux:select wire:model="gate_pass_approval_id" variant="listbox" searchable clearable :filter="false" label="Gate Pass Ref" placeholder="If credit delivery…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="gatePassSearch" placeholder="Search gate pass…" /></x-slot>
                        @foreach ($this->gatePasses as $gp)<flux:select.option :value="$gp->id" wire:key="gp-{{ $gp->id }}">{{ $gp->approval_no }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3" x-show="$wire.movement_type === 'outward'" x-cloak>
                    <flux:select wire:model.live="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="JC…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                        @foreach ($this->jobCards as $jc)<flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="driver_type" variant="listbox" clearable label="Driver Type" placeholder="Who's driving…">
                        @foreach ($VM::driverTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- PEOPLE & TIME --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">People & Time</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Who cleared it and when. TAT is derived from entry → exit.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="delivered_by_id" variant="listbox" searchable clearable label="Delivered By (Driver / Advisor)" placeholder="Employee…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="db-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="security_guard_id" variant="listbox" searchable clearable label="Exit By (Security Guard)" placeholder="Guard…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="sg-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="grid grid-cols-2 gap-2">
                        <flux:date-picker locale="en-IN" wire:model="entry_at" label="Entry Date & Time" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                        <flux:time-picker wire:model="entry_at_time" label="Time" />
                    </div>
                    <div>
                        <div class="grid grid-cols-2 gap-2">
                            <flux:date-picker locale="en-IN" wire:model="exit_at" label="Exit Date & Time" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                            <flux:time-picker wire:model="exit_at_time" label="Time" />
                        </div>
                        <flux:error name="exit_at" />
                    </div>
                </div>

                {{-- Image capture --}}
                <div class="pt-2">
                    <div class="flex items-center justify-between mb-2">
                        <flux:text size="sm" class="font-medium">Image Capture</flux:text>
                        <flux:button type="button" size="sm" variant="ghost" icon="camera" wire:click="addAttachment">Add photo</flux:button>
                    </div>
                    @forelse ($attachments as $i => $att)
                        <div wire:key="att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2 items-end mb-2 p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                            <flux:select wire:model="attachments.{{ $i }}.attachment_type" variant="listbox" size="sm" clearable label="Type" placeholder="Type…">
                                @foreach ($ATT::attachmentTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                            </flux:select>
                            <div>
                                <flux:input type="file" wire:model="attachmentFiles.{{ $i }}" size="sm" accept=".jpg,.jpeg,.png,.webp,.pdf" />
                                @if (! empty($att['path']))<flux:text size="sm" class="text-zinc-500 mt-1">Current: {{ $att['original_name'] ?? basename($att['path']) }}</flux:text>@endif
                                <flux:error name="attachmentFiles.{{ $i }}" />
                            </div>
                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeAttachment({{ $i }})" class="h-9!" />
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Entry / exit / number-plate photo.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Gate remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('vehicle-movement.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Log movement' }}</flux:button>
        </div>
    </form>
</div>
