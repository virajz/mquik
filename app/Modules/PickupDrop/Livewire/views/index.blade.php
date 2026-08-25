<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Pickup / Drop</flux:heading>
            <flux:text class="mt-1">Vehicle pickup and drop scheduling — driver/vendor assignment and status tracking.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('pickup_drop.create')
                <flux:button variant="primary" icon="plus" :href="route('pickup-drop.create')" wire:navigate>New Pickup / Drop</flux:button>
            @endcan
        </div>
    </div>

    {{-- Search and status on the surface; everything else behind one button,
         same pattern as the Appointment listing. --}}
    <div class="mb-3 flex items-center gap-3">
        <div class="flex-1 min-w-0">
            <flux:input wire:model.live.debounce.300ms="search"
                placeholder="PD no, job card no, customer, phone, reg no, brand/model…"
                icon="magnifying-glass" clearable class="w-full" />
        </div>

        <div class="w-44 shrink-0">
            <flux:select wire:model.live="statusFilter" variant="listbox" class="w-full">
                <flux:select.option value="open">Open (unfinished)</flux:select.option>
                <flux:select.option value="all">All status</flux:select.option>
                @foreach ($statuses as $key => $label)
                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @php($filtersOn = $directionFilter !== 'all' || $driverFilter !== 'all' || $slotFilter !== 'all' || $typeFilter !== 'all' || $dateFrom || $dateTo || $stageFilter !== 'all' || $dateField !== 'scheduled_at')
        <flux:dropdown class="shrink-0">
            <flux:button icon="funnel" variant="{{ $filtersOn ? 'primary' : 'outline' }}">Filters</flux:button>

            <flux:popover class="w-80 space-y-4">
                <flux:select wire:model.live="directionFilter" variant="listbox" label="Direction">
                    <flux:select.option value="all">Both</flux:select.option>
                    @foreach ($directions as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="driverFilter" variant="listbox" searchable label="Driver">
                    <flux:select.option value="all">All drivers</flux:select.option>
                    @foreach ($this->drivers as $d)
                        <flux:select.option :value="(string) $d->id">{{ $d->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="slotFilter" variant="listbox" searchable label="Time Slot">
                    <flux:select.option value="all">All time slots</flux:select.option>
                    @foreach ($this->timeSlots as $slot)
                        <flux:select.option :value="(string) $slot->id" wire:key="fslot-{{ $slot->id }}">{{ $slot->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="typeFilter" variant="listbox" searchable label="Pickup/Drop Type">
                    <flux:select.option value="all">All types</flux:select.option>
                    @foreach ($this->pickupDropOptions as $opt)
                        <flux:select.option :value="(string) $opt->id" wire:key="ftype-{{ $opt->id }}">{{ $opt->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:separator variant="subtle" />

                <flux:select wire:model.live="dateField" variant="listbox" label="Date range applies to">
                    <flux:select.option value="scheduled_at">Scheduled date</flux:select.option>
                    <flux:select.option value="created_at">Created date</flux:select.option>
                </flux:select>

                <div class="grid grid-cols-2 gap-2">
                    <flux:date-picker locale="en-IN" wire:model.live="dateFrom" placeholder="From" with-today selectable-header fixed-weeks type="input" clearable />
                    <flux:date-picker locale="en-IN" wire:model.live="dateTo" placeholder="To" with-today selectable-header fixed-weeks type="input" clearable />
                </div>
            </flux:popover>
        </flux:dropdown>

        @if ($search || $statusFilter !== 'open' || $filtersOn)
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters" class="shrink-0">Clear</flux:button>
        @endif

        <flux:button variant="{{ $showDriverBoard ? 'primary' : 'outline' }}" size="sm" icon="users" wire:click="$toggle('showDriverBoard')" class="shrink-0">
            Driver board
        </flux:button>
    </div>

    {{-- Per-driver workload. A count is a filter: one click narrows the table
         to that driver's assigned / collected / still-waiting jobs. --}}
    @if ($showDriverBoard)
        <div class="mb-4 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Driver</flux:table.column>
                    <flux:table.column align="end">Assigned</flux:table.column>
                    <flux:table.column align="end">Collected</flux:table.column>
                    <flux:table.column align="end">Collection Pending</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->driverBoard as $driverRow)
                        <flux:table.row wire:key="db-{{ $driverRow['id'] }}">
                            <flux:table.cell class="font-medium">{{ $driverRow['name'] }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="xs" variant="ghost" wire:click="focusDriver({{ $driverRow['id'] }}, 'all')">{{ $driverRow['assigned'] }}</flux:button>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="xs" variant="ghost" wire:click="focusDriver({{ $driverRow['id'] }}, 'collected')">{{ $driverRow['collected'] }}</flux:button>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="xs" variant="ghost" wire:click="focusDriver({{ $driverRow['id'] }}, 'awaiting')">{{ $driverRow['awaiting'] }}</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center text-zinc-500 py-6">No open jobs are assigned to a driver.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    @endif

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'pickup_drop_no'" :direction="$sortDirection" wire:click="sort('pickup_drop_no')">No.</flux:table.column>
            <flux:table.column class="w-44" sortable :sorted="$sortBy === 'scheduled_at'" :direction="$sortDirection" wire:click="sort('scheduled_at')">When</flux:table.column>
            <flux:table.column class="w-24">Direction</flux:table.column>
            <flux:table.column>Customer / Vehicle</flux:table.column>
            <flux:table.column class="w-40">Advisor / Dept</flux:table.column>
            <flux:table.column class="w-44">Assigned</flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-40">Pending Reason</flux:table.column>
            <flux:table.column class="w-28">Job Card</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->pickup_drop_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div class="font-medium">{{ $row->scheduled_at?->format('d/m/Y') }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">
                            {{ $row->scheduled_at?->format('h:i A') }}{{ $row->timeSlot ? ' · '.$row->timeSlot->name : '' }}
                        </div>
                        <div class="text-xs text-zinc-400 mt-0.5">Entered {{ $row->created_at?->format('d/m/Y') }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$row->direction === 'pickup' ? 'blue' : 'sky'" size="sm">{{ $directions[$row->direction] ?? $row->direction }}</flux:badge>
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
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $row->advisor?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->workshopDepartment?->name }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        @if ($row->driver)
                            <div>{{ $row->driver->name }}</div>
                            <div class="text-xs text-zinc-500 mt-0.5">In-house</div>
                        @elseif ($row->vendor)
                            <div>{{ $row->vendor->name }}</div>
                            <div class="text-xs text-zinc-500 mt-0.5">Vendor</div>
                        @else
                            <span class="text-zinc-400">— unassigned —</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($statusColor = match ($row->status) {
                            'pending' => 'amber', 'driver_assigned' => 'blue', 'driver_on_the_way' => 'sky',
                            'vehicle_collected' => 'indigo', 'vehicle_delivered' => 'lime',
                            'completed' => 'green', 'cancelled' => 'zinc', default => 'zinc',
                        })
                        <flux:badge :color="$statusColor" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>

                    {{-- Why a stuck job is stuck — what this list is scanned for. --}}
                    <flux:table.cell class="text-sm text-zinc-500">
                        @if ($row->status === \App\Modules\PickupDrop\Models\PickupDrop::STATUS_PENDING)
                            {{ $row->pendingReason?->name ?? 'Not specified' }}
                        @else
                            —
                        @endif
                    </flux:table.cell>
                    {{-- Linked after the vehicle is received. --}}
                    <flux:table.cell class="font-mono text-xs">
                        @if ($row->jobCard)
                            <flux:link :href="route('job-card.edit', $row->job_card_id)" wire:navigate>{{ $row->jobCard->job_card_no }}</flux:link>
                        @else
                            —
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('pickup_drop.update')
                                @if (! $row->cancelled_at && $row->status !== \App\Modules\PickupDrop\Models\PickupDrop::STATUS_COMPLETED)
                                    <flux:tooltip content="Cancel with a reason">
                                        <flux:modal.trigger :name="'pickup-drop-cancel-' . $row->id">
                                            <flux:button size="sm" variant="ghost" icon="x-circle" />
                                        </flux:modal.trigger>
                                    </flux:tooltip>
                                    <flux:modal :name="'pickup-drop-cancel-' . $row->id" class="md:w-96">
                                        <div class="space-y-4">
                                            <flux:heading size="lg">Cancel {{ $row->pickup_drop_no }}?</flux:heading>
                                            <flux:select wire:model="cancelReasonId" variant="listbox" label="Cancel Reason" placeholder="Why is it being cancelled?">
                                                @foreach ($this->cancelReasons as $r)
                                                    <flux:select.option :value="$r->id" wire:key="cxl-{{ $row->id }}-{{ $r->id }}">{{ $r->name }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            <flux:error name="cancelReasonId" />
                                            <div class="flex gap-2 justify-end">
                                                <flux:modal.close><flux:button variant="ghost">Keep it</flux:button></flux:modal.close>
                                                <flux:button variant="danger" wire:click="cancelRow({{ $row->id }})">Cancel Job</flux:button>
                                            </div>
                                        </div>
                                    </flux:modal>
                                @endif
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('pickup-drop.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('pickup_drop.delete')
                                <flux:modal.trigger :name="'pickup-drop-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'pickup-drop-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->pickup_drop_no }}?</flux:heading>
                                        <flux:text>Cannot be undone.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('pickup-drop-delete-{{ $row->id }}').close()">Delete</flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="10" class="text-center text-zinc-500 py-12">
                        <flux:icon.truck class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No pickup / drop runs scheduled</div>
                        <flux:text class="mt-1">Schedule from an appointment, or create one directly here.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
