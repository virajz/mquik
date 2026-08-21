<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Appointments</flux:heading>
            <flux:text class="mt-1">Customer service bookings — when, who, which vehicle, which advisor.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('appointment.create')
                <flux:button variant="primary" icon="plus" :href="route('appointment.create')" wire:navigate>New Appointment</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by appointment no, customer, phone, reg no..." icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="channelFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All channels</flux:select.option>
            @foreach ($channels as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="advisorFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All advisors</flux:select.option>
            @foreach ($this->advisors as $a)
                <flux:select.option :value="(string) $a->id">{{ $a->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="deptFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All departments</flux:select.option>
            @foreach ($this->departments as $d)
                <flux:select.option :value="(string) $d->id">{{ $d->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:date-picker wire:model.live="dateFrom" placeholder="From date" with-today selectable-header fixed-weeks type="input" clearable class="max-w-44" />
        <flux:date-picker wire:model.live="dateTo" placeholder="To date" with-today selectable-header fixed-weeks type="input" clearable class="max-w-44" />
        @if ($search || $statusFilter !== 'all' || $channelFilter !== 'all' || $advisorFilter !== 'all' || $deptFilter !== 'all' || $dateFrom || $dateTo)
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'appointment_no'" :direction="$sortDirection" wire:click="sort('appointment_no')">No.</flux:table.column>
            <flux:table.column class="w-44" sortable :sorted="$sortBy === 'appointment_at'" :direction="$sortDirection" wire:click="sort('appointment_at')">When</flux:table.column>
            <flux:table.column>Customer / Vehicle</flux:table.column>
            <flux:table.column class="w-40">Advisor / Dept</flux:table.column>
            <flux:table.column class="w-32">Channel</flux:table.column>
            <flux:table.column class="w-24">Priority</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-40">Pending Reason</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->appointment_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div class="font-medium">{{ $row->appointment_at?->format('d M Y') }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">
                            {{ $row->timeSlot?->window() ?? $row->appointment_at?->format('h:i A') }}
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium">{{ trim($row->customer?->first_name.' '.($row->customer?->last_name ?? '')) }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 flex items-center gap-2">
                            @if ($row->customer?->phone)
                                <span class="font-mono">+91 {{ $row->customer->phone }}</span>
                            @endif
                            @if ($row->customerVehicle)
                                <span>·</span>
                                <span class="font-mono">{{ $row->customerVehicle->registration_no }}</span>
                                <span>·</span>
                                <span>{{ trim(($row->customerVehicle->model?->brand?->name ?? '').' '.($row->customerVehicle->model?->name ?? '')) }}</span>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $row->advisor?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->workshopDepartment?->name ?? '—' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">
                        {{ $row->bookingChannel?->name ?? '—' }}
                        @if ($row->pickupDropOption?->involves_pickup || $row->pickupDropOption?->involves_drop)
                            <div class="mt-0.5">
                                <flux:badge color="amber" size="sm">{{ $row->pickupDropOption->name }}</flux:badge>
                            </div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">
                        @if ($row->priority)
                            @php($priorityColor = match (strtoupper($row->priority->name)) {
                                'URGENT' => 'red', 'HIGH' => 'amber', default => 'zinc',
                            })
                            <flux:badge :color="$priorityColor" size="sm">{{ $row->priority->name }}</flux:badge>
                        @else
                            —
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($statusColor = match ($row->status) {
                            'pending' => 'amber', 'confirmed' => 'blue', 'rescheduled' => 'purple',
                            'completed' => 'lime', 'cancelled' => 'zinc', 'no_show' => 'red', default => 'zinc',
                        })
                        <flux:badge :color="$statusColor" size="sm">{{ $row->effectiveStatusLabel() }}</flux:badge>
                    </flux:table.cell>

                    {{-- Only meaningful while a booking is pending — why it is stuck
                         is the thing a coordinator scans this list for. --}}
                    <flux:table.cell class="text-sm text-zinc-500">
                        @if ($row->status === \App\Modules\Appointment\Models\Appointment::STATUS_PENDING)
                            {{ $row->pendingReason?->name ?? 'Not specified' }}
                        @else
                            —
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('job_card.create')
                                <flux:tooltip content="Create job card from this appointment">
                                    <flux:button size="sm" variant="ghost" icon="clipboard-document-check" :href="route('job-card.create', ['from-appointment' => $row->id])" wire:navigate />
                                </flux:tooltip>
                            @endcan
                            @can('pickup_drop.create')
                                <flux:tooltip content="Create pickup / drop from this appointment">
                                    <flux:button size="sm" variant="ghost" icon="truck" :href="route('pickup-drop.create', ['from-appointment' => $row->id])" wire:navigate />
                                </flux:tooltip>
                            @endcan
                            @can('appointment.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('appointment.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('appointment.delete')
                                <flux:modal.trigger :name="'appointment-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'appointment-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->appointment_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. If this appointment has spawned a job card, the delete will fail.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('appointment-delete-{{ $row->id }}').close()">Delete</flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center text-zinc-500 py-12">
                        <flux:icon.calendar-days class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No appointments yet</div>
                        <flux:text class="mt-1">Booking from app, website, email, or phone — they all land here.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
