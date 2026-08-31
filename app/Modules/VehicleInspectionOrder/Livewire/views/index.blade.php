<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Vehicle Inspection Orders</flux:heading>
            <flux:text class="mt-1">VIO/VIR — technician + bay assignment, time tracking, item-wise results and before/after photos.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('vehicle_inspection_order.create')
                <flux:button variant="primary" icon="plus" :href="route('vehicle-inspection-order.create')" wire:navigate>New Work Order</flux:button>
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

    <div class="mb-4 flex items-center gap-3">
        <div class="flex-1 min-w-0">
            <flux:input wire:model.live.debounce.300ms="search"
                placeholder="Search by vehicle + reg. no (space or %), VIO no, JC no, customer…"
                icon="magnifying-glass" clearable class="w-full" />
        </div>

        <div class="w-44 shrink-0">
            <flux:select wire:model.live="statusFilter" variant="listbox" class="w-full">
                <flux:select.option value="all">All status</flux:select.option>
                @foreach ($statuses as $key => $label)
                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @php($filtersOn = $priorityFilter !== 'all' || $technicianFilter !== 'all' || $bayFilter !== 'all'
            || $departmentFilter !== 'all' || $serviceTypeFilter !== 'all' || $advisorFilter !== 'all'
            || $dateFrom || $dateTo || $dateField !== 'ordered_at')
        <flux:dropdown class="shrink-0">
            <flux:button icon="funnel" variant="{{ $filtersOn ? 'primary' : 'outline' }}">Filters</flux:button>

            <flux:popover class="w-80 space-y-4">
                <flux:select wire:model.live="departmentFilter" variant="listbox" searchable label="Department">
                    <flux:select.option value="all">All departments</flux:select.option>
                    @foreach ($this->departments as $d)
                        <flux:select.option :value="(string) $d->id" wire:key="fdept-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                {{-- Service types narrow to the chosen department, the same order
                     the advisor picks them in on the order itself. --}}
                <flux:select wire:model.live="serviceTypeFilter" variant="listbox" searchable label="Service Type">
                    <flux:select.option value="all">All service types</flux:select.option>
                    @foreach ($this->serviceTypes as $st)
                        <flux:select.option :value="(string) $st->id" wire:key="fst-{{ $st->id }}">{{ $st->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="advisorFilter" variant="listbox" searchable label="Advisor">
                    <flux:select.option value="all">All advisors</flux:select.option>
                    @foreach ($advisors as $a)
                        <flux:select.option :value="(string) $a->id" wire:key="fadv-{{ $a->id }}">{{ $a->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="technicianFilter" variant="listbox" searchable label="Technician">
                    <flux:select.option value="all">All technicians</flux:select.option>
                    @foreach ($this->technicians as $t)
                        <flux:select.option :value="(string) $t->id" wire:key="ftech-{{ $t->id }}">{{ $t->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="bayFilter" variant="listbox" searchable label="Bay">
                    <flux:select.option value="all">All bays</flux:select.option>
                    @foreach ($this->bays as $b)
                        <flux:select.option :value="(string) $b->id" wire:key="fbay-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="priorityFilter" variant="listbox" label="Priority">
                    <flux:select.option value="all">All priority</flux:select.option>
                    @foreach ($priorities as $key => $label)
                        <flux:select.option :value="(string) $key" wire:key="fpri-{{ $key }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:separator variant="subtle" />

                <flux:select wire:model.live="dateField" variant="listbox" label="Date range applies to">
                    <flux:select.option value="ordered_at">Inspection order date</flux:select.option>
                    <flux:select.option value="started_at">Work start date</flux:select.option>
                    <flux:select.option value="ended_at">Work complete date</flux:select.option>
                    <flux:select.option value="created_at">Created date</flux:select.option>
                </flux:select>

                <div class="grid grid-cols-2 gap-2">
                    <flux:date-picker locale="en-IN" wire:model.live="dateFrom" placeholder="From" with-today selectable-header fixed-weeks type="input" clearable />
                    <flux:date-picker locale="en-IN" wire:model.live="dateTo" placeholder="To" with-today selectable-header fixed-weeks type="input" clearable />
                </div>
            </flux:popover>
        </flux:dropdown>

        @if ($search || $statusFilter !== 'all' || $filtersOn)
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters" class="shrink-0">Clear</flux:button>
        @endif

        @can('inspection_order_history.view')
            <flux:button variant="outline" size="sm" icon="chart-bar" :href="route('inspection-order-tat.index')" wire:navigate class="shrink-0">
                Technician TAT
            </flux:button>
        @endcan
    </div>

    @if ($filtersOn || $search || $statusFilter !== 'all')
        <div class="mb-3 flex flex-wrap items-center gap-1.5">
            @if ($search)
                <flux:badge size="sm" variant="pill">Search: {{ $search }}<flux:badge.close wire:click="$set('search', '')" /></flux:badge>
            @endif
            @if ($statusFilter !== 'all')
                <flux:badge size="sm" variant="pill" color="blue">{{ $statuses[$statusFilter] ?? $statusFilter }}<flux:badge.close wire:click="$set('statusFilter', 'all')" /></flux:badge>
            @endif
            @if ($departmentFilter !== 'all')
                <flux:badge size="sm" variant="pill">{{ $this->departments->firstWhere('id', (int) $departmentFilter)?->name }}<flux:badge.close wire:click="$set('departmentFilter', 'all')" /></flux:badge>
            @endif
            @if ($serviceTypeFilter !== 'all')
                <flux:badge size="sm" variant="pill">{{ $this->serviceTypes->firstWhere('id', (int) $serviceTypeFilter)?->name }}<flux:badge.close wire:click="$set('serviceTypeFilter', 'all')" /></flux:badge>
            @endif
            @if ($advisorFilter !== 'all')
                <flux:badge size="sm" variant="pill">Advisor: {{ $advisors->firstWhere('id', (int) $advisorFilter)?->name }}<flux:badge.close wire:click="$set('advisorFilter', 'all')" /></flux:badge>
            @endif
            @if ($technicianFilter !== 'all')
                <flux:badge size="sm" variant="pill">Tech: {{ $this->technicians->firstWhere('id', (int) $technicianFilter)?->name }}<flux:badge.close wire:click="$set('technicianFilter', 'all')" /></flux:badge>
            @endif
            @if ($bayFilter !== 'all')
                <flux:badge size="sm" variant="pill">Bay: {{ $this->bays->firstWhere('id', (int) $bayFilter)?->name }}<flux:badge.close wire:click="$set('bayFilter', 'all')" /></flux:badge>
            @endif
            @if ($priorityFilter !== 'all')
                <flux:badge size="sm" variant="pill">{{ $priorities[$priorityFilter] ?? $priorityFilter }}<flux:badge.close wire:click="$set('priorityFilter', 'all')" /></flux:badge>
            @endif
            @if ($dateFrom || $dateTo)
                @php($dateLabel = ['ordered_at' => 'Ordered', 'started_at' => 'Started', 'ended_at' => 'Completed', 'created_at' => 'Created'][$dateField] ?? 'Ordered')
                <flux:badge size="sm" variant="pill">
                    {{ $dateLabel }}
                    {{ $dateFrom ? \Illuminate\Support\Carbon::parse($dateFrom)->format('d/m/Y') : '…' }}
                    –
                    {{ $dateTo ? \Illuminate\Support\Carbon::parse($dateTo)->format('d/m/Y') : '…' }}
                    <flux:badge.close wire:click="clearDateRange" />
                </flux:badge>
            @endif
        </div>
    @endif

    <div class="overflow-x-auto">
    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'order_no'" :direction="$sortDirection" wire:click="sort('order_no')">No.</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'ordered_at'" :direction="$sortDirection" wire:click="sort('ordered_at')">Ordered</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'job_card_no'" :direction="$sortDirection" wire:click="sort('job_card_no')">Job Card</flux:table.column>
            <flux:table.column class="w-44" sortable :sorted="$sortBy === 'registration_no'" :direction="$sortDirection" wire:click="sort('registration_no')">Vehicle</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'department'" :direction="$sortDirection" wire:click="sort('department')">Department</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'service_type'" :direction="$sortDirection" wire:click="sort('service_type')">Service Type</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'advisor'" :direction="$sortDirection" wire:click="sort('advisor')">Advisor</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'technician'" :direction="$sortDirection" wire:click="sort('technician')">Technician</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'bay'" :direction="$sortDirection" wire:click="sort('bay')">Bay</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'started_at'" :direction="$sortDirection" wire:click="sort('started_at')">Work Start</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'ended_at'" :direction="$sortDirection" wire:click="sort('ended_at')">Work Complete</flux:table.column>
            <flux:table.column class="w-20 text-center" sortable :sorted="$sortBy === 'items_count'" :direction="$sortDirection" wire:click="sort('items_count')">Items</flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'priority_id'" :direction="$sortDirection" wire:click="sort('priority_id')">Priority</flux:table.column>
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->order_no ?? '—' }}</flux:table.cell>

                    {{-- When the order was raised, distinct from when a technician
                         picked it up — the gap between the two is the queue. --}}
                    <flux:table.cell class="text-sm whitespace-nowrap">
                        @if ($row->ordered_at)
                            <div>{{ $row->ordered_at->format('d/m/Y') }}</div>
                            <div class="text-xs text-zinc-500 mt-0.5">{{ $row->ordered_at->format('h:i A') }}</div>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="font-mono text-xs">{{ $row->jobCard?->job_card_no ?? '—' }}</flux:table.cell>

                    <flux:table.cell class="text-sm">
                        <div class="font-mono">{{ $row->jobCard?->customerVehicle?->registration_no ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->jobCard?->customerVehicle?->model?->name ?? '—' }}</div>
                    </flux:table.cell>

                    <flux:table.cell class="text-sm">{{ $row->department?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->serviceType?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->advisor?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->technician?->name ?? '— unassigned —' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->bay?->name ?? '—' }}</flux:table.cell>

                    <flux:table.cell class="text-sm whitespace-nowrap">
                        @if ($row->started_at)
                            <div>{{ $row->started_at->format('d/m/Y') }}</div>
                            <div class="text-xs text-zinc-500 mt-0.5">{{ $row->started_at->format('h:i A') }}</div>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="text-sm whitespace-nowrap">
                        @if ($row->ended_at)
                            <div>{{ $row->ended_at->format('d/m/Y') }}</div>
                            <div class="text-xs text-zinc-500 mt-0.5">{{ $row->ended_at->format('h:i A') }}</div>
                        @else
                            <span class="text-zinc-400">—</span>
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
                            @can('vehicle_inspection_order.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('vehicle-inspection-order.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('vehicle_inspection_order.delete')
                                <flux:modal.trigger :name="'vio-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'vio-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->order_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Cascades to items and pause records.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('vio-delete-{{ $row->id }}').close()">Delete</flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="15" class="text-center text-zinc-500 py-12">
                        <flux:icon.clipboard-document-check class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No work orders yet</div>
                        <flux:text class="mt-1">Create one from a job card to assign a technician and bay.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    </div>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
