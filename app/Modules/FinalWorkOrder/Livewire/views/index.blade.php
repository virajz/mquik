<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Final Work Orders</flux:heading>
            <flux:text class="mt-1">Post-approval work orders — technician + bay assignment, time tracking, item-wise results and photos.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('final_work_order.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Work Order Analysis</flux:button>
            @endcan
            @can('final_work_order.create')
                <flux:button variant="primary" icon="plus" :href="route('final-work-order.create')" wire:navigate>New Work Order</flux:button>
            @endcan
        </div>
    </div>

    {{-- Inspection counters --}}
    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ([
            ['label' => 'Pending', 'value' => $kpis['pending'], 'icon' => 'inbox', 'filter' => 'assignment_pending'],
            ['label' => 'Active', 'value' => $kpis['active'], 'icon' => 'wrench-screwdriver', 'filter' => 'wip'],
            ['label' => 'Completed', 'value' => $kpis['completed'], 'icon' => 'check-circle', 'filter' => 'completed'],
            ['label' => 'Cancelled', 'value' => $kpis['cancelled'], 'icon' => 'x-circle', 'filter' => 'cancelled'],
        ] as $kpi)
            <button type="button" wire:click="$set('statusFilter', '{{ $kpi['filter'] }}')"
                class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
                <div class="flex items-center gap-2 text-zinc-500">
                    <flux:icon :name="$kpi['icon']" class="size-4" />
                    <flux:text size="sm">{{ $kpi['label'] }}</flux:text>
                </div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpi['value'] }}</div>
            </button>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by FWO no, JC no, or reg no..." icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="priorityFilter" variant="listbox" class="max-w-36">
            <flux:select.option value="all">All priority</flux:select.option>
            @foreach ($priorities as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="technicianFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All technicians</flux:select.option>
            @foreach ($this->technicians as $t)
                <flux:select.option :value="(string) $t->id">{{ $t->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="bayFilter" variant="listbox" searchable class="max-w-44">
            <flux:select.option value="all">All bays</flux:select.option>
            @foreach ($this->bays as $b)
                <flux:select.option :value="(string) $b->id">{{ $b->name }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $priorityFilter !== 'all' || $technicianFilter !== 'all' || $bayFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'order_no'" :direction="$sortDirection" wire:click="sort('order_no')">No.</flux:table.column>
            <flux:table.column>Job Card / Vehicle</flux:table.column>
            <flux:table.column class="w-40">Technician / Bay</flux:table.column>
            <flux:table.column class="w-24 text-center">Items</flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'priority_id'" :direction="$sortDirection" wire:click="sort('priority_id')">Priority</flux:table.column>
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->order_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-mono font-medium text-sm">{{ $row->jobCard?->job_card_no ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 flex items-center gap-2">
                            @if ($row->jobCard?->customerVehicle)
                                <span class="font-mono">{{ $row->jobCard->customerVehicle->registration_no }}</span>
                                <span>·</span>
                            @endif
                            <span>{{ trim($row->jobCard?->customer?->first_name.' '.($row->jobCard?->customer?->last_name ?? '')) }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $row->technician?->name ?? '— unassigned —' }}</div>
                        @if ($row->bay)
                            <div class="text-xs text-zinc-500 mt-0.5">{{ $row->bay->name }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-center font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell>
                        @php($priorityColor = match (strtoupper($row->priority?->name ?? '')) {
                            'URGENT', 'CRITICAL', 'BREAKDOWN' => 'red', 'HIGH' => 'amber', default => 'zinc',
                        })
                        <flux:badge :color="$priorityColor" size="sm">{{ $row->priority?->name ?? '—' }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($statusColor = match ($row->status) {
                            'assignment_pending' => 'amber', 'assigned' => 'sky', 'wip' => 'blue',
                            'on_hold' => 'orange', 'completed' => 'lime', 'cancelled' => 'zinc',
                            default => 'zinc',
                        })
                        <flux:badge :color="$statusColor" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('final_work_order.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('final-work-order.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('final_work_order.delete')
                                <flux:modal.trigger :name="'fwo-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'fwo-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->order_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Cascades to items and pause records.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('fwo-delete-{{ $row->id }}').close()">Delete</flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500 py-12">
                        <flux:icon.clipboard-document-check class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No work orders yet</div>
                        <flux:text class="mt-1">Create one from a job card to assign a technician and bay.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
