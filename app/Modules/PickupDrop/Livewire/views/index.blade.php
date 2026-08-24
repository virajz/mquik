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

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by no, customer, phone, reg no..." icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="directionFilter" variant="listbox" class="max-w-32">
            <flux:select.option value="all">Both</flux:select.option>
            @foreach ($directions as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="driverFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All drivers</flux:select.option>
            @foreach ($this->drivers as $d)
                <flux:select.option :value="(string) $d->id">{{ $d->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:date-picker wire:model.live="dateFrom" placeholder="From date" with-today selectable-header fixed-weeks type="input" clearable class="max-w-44" />
        <flux:date-picker wire:model.live="dateTo" placeholder="To date" with-today selectable-header fixed-weeks type="input" clearable class="max-w-44" />
        @if ($search || $statusFilter !== 'all' || $directionFilter !== 'all' || $driverFilter !== 'all' || $dateFrom || $dateTo || $stageFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
        <flux:spacer />
        <flux:button variant="{{ $showDriverBoard ? 'primary' : 'outline' }}" size="sm" icon="users" wire:click="$toggle('showDriverBoard')">
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
            <flux:table.column class="w-44">Assigned</flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-40">Pending Reason</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->pickup_drop_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div class="font-medium">{{ $row->scheduled_at?->format('d M Y') }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->scheduled_at?->format('h:i A') }}</div>
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
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('pickup_drop.update')
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
                    <flux:table.cell colspan="8" class="text-center text-zinc-500 py-12">
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
