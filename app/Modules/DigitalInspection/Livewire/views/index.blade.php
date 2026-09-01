<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Digital Inspections</flux:heading>
            <flux:text class="mt-1">Per-job inspection checklists — Rep / Adj / OK / IA / FA outcomes captured live.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('digital_inspection.create')
                <flux:button variant="primary" icon="plus" :href="route('digital-inspection.create')" wire:navigate>New Inspection</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3">
        <div class="flex-1 min-w-0">
            <flux:input wire:model.live.debounce.300ms="search"
                placeholder="Search by vehicle + reg. no (space or %), inspection no, JC no, customer…"
                icon="magnifying-glass" clearable class="w-full" />
        </div>

        <div class="w-48 shrink-0">
            <flux:select wire:model.live="statusFilter" variant="listbox" class="w-full">
                <flux:select.option value="pending">Pending (open)</flux:select.option>
                <flux:select.option value="all">All status</flux:select.option>
                @foreach ($statuses as $key => $label)
                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @php($filtersOn = $technicianFilter !== 'all' || $templateFilter !== 'all' || $departmentFilter !== 'all'
            || $serviceTypeFilter !== 'all' || $advisorFilter !== 'all' || $floorFilter !== 'all'
            || $dateFrom || $dateTo || $dateField !== 'created_at')
        <flux:dropdown class="shrink-0">
            <flux:button icon="funnel" variant="{{ $filtersOn ? 'primary' : 'outline' }}">Filters</flux:button>

            <flux:popover class="w-80 space-y-4">
                <flux:select wire:model.live="departmentFilter" variant="listbox" searchable label="Department">
                    <flux:select.option value="all">All departments</flux:select.option>
                    @foreach ($departments as $d)
                        <flux:select.option :value="(string) $d->id" wire:key="fdept-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="serviceTypeFilter" variant="listbox" searchable label="Service Type">
                    <flux:select.option value="all">All service types</flux:select.option>
                    @foreach ($serviceTypes as $st)
                        <flux:select.option :value="(string) $st->id" wire:key="fst-{{ $st->id }}">{{ $st->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="advisorFilter" variant="listbox" searchable label="Advisor">
                    <flux:select.option value="all">All advisors</flux:select.option>
                    @foreach ($this->advisors as $e)
                        <flux:select.option :value="(string) $e->id" wire:key="fadv-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="floorFilter" variant="listbox" searchable label="Floor In-charge">
                    <flux:select.option value="all">All floor in-charges</flux:select.option>
                    @foreach ($this->floorIncharges as $e)
                        <flux:select.option :value="(string) $e->id" wire:key="ffl-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="technicianFilter" variant="listbox" searchable label="Technician">
                    <flux:select.option value="all">All technicians</flux:select.option>
                    @foreach ($this->technicians as $e)
                        <flux:select.option :value="(string) $e->id" wire:key="ftech-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="templateFilter" variant="listbox" searchable label="Template">
                    <flux:select.option value="all">All templates</flux:select.option>
                    @foreach ($this->templates as $t)
                        <flux:select.option :value="(string) $t->id" wire:key="ftpl-{{ $t->id }}">{{ $t->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:separator variant="subtle" />

                <flux:select wire:model.live="dateField" variant="listbox" label="Date range applies to">
                    <flux:select.option value="created_at">Inspection date</flux:select.option>
                    <flux:select.option value="started_at">Work start date</flux:select.option>
                    <flux:select.option value="completed_at">Work complete date</flux:select.option>
                </flux:select>

                <div class="grid grid-cols-2 gap-2">
                    <flux:date-picker locale="en-IN" wire:model.live="dateFrom" placeholder="From" with-today selectable-header fixed-weeks type="input" clearable />
                    <flux:date-picker locale="en-IN" wire:model.live="dateTo" placeholder="To" with-today selectable-header fixed-weeks type="input" clearable />
                </div>
            </flux:popover>
        </flux:dropdown>

        @if ($search || $statusFilter !== 'pending' || $filtersOn)
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters" class="shrink-0">Clear</flux:button>
        @endif

        @can('inspection_report.view')
            <flux:button variant="outline" size="sm" icon="chart-bar" :href="route('inspection-report.index')" wire:navigate class="shrink-0">
                Reports
            </flux:button>
        @endcan
    </div>

    @if ($search || $statusFilter !== 'pending' || $filtersOn)
        <div class="mb-3 flex flex-wrap items-center gap-1.5">
            @if ($search)
                <flux:badge size="sm" variant="pill">Search: {{ $search }}<flux:badge.close wire:click="$set('search', '')" /></flux:badge>
            @endif
            @if ($statusFilter !== 'pending')
                <flux:badge size="sm" variant="pill" color="blue">{{ $statusFilter === 'all' ? 'All status' : ($allStatuses[$statusFilter] ?? $statusFilter) }}<flux:badge.close wire:click="$set('statusFilter', 'pending')" /></flux:badge>
            @endif
            @if ($departmentFilter !== 'all')
                <flux:badge size="sm" variant="pill">{{ $departments->firstWhere('id', (int) $departmentFilter)?->name }}<flux:badge.close wire:click="$set('departmentFilter', 'all')" /></flux:badge>
            @endif
            @if ($serviceTypeFilter !== 'all')
                <flux:badge size="sm" variant="pill">{{ $serviceTypes->firstWhere('id', (int) $serviceTypeFilter)?->name }}<flux:badge.close wire:click="$set('serviceTypeFilter', 'all')" /></flux:badge>
            @endif
            @if ($advisorFilter !== 'all')
                <flux:badge size="sm" variant="pill">Advisor: {{ $this->advisors->firstWhere('id', (int) $advisorFilter)?->name }}<flux:badge.close wire:click="$set('advisorFilter', 'all')" /></flux:badge>
            @endif
            @if ($floorFilter !== 'all')
                <flux:badge size="sm" variant="pill">Floor: {{ $this->floorIncharges->firstWhere('id', (int) $floorFilter)?->name }}<flux:badge.close wire:click="$set('floorFilter', 'all')" /></flux:badge>
            @endif
            @if ($technicianFilter !== 'all')
                <flux:badge size="sm" variant="pill">Tech: {{ $this->technicians->firstWhere('id', (int) $technicianFilter)?->name }}<flux:badge.close wire:click="$set('technicianFilter', 'all')" /></flux:badge>
            @endif
            @if ($dateFrom || $dateTo)
                @php($dateLabel = ['created_at' => 'Inspected', 'started_at' => 'Started', 'completed_at' => 'Completed'][$dateField] ?? 'Inspected')
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
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'inspection_no'" :direction="$sortDirection" wire:click="sort('inspection_no')">No.</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Inspected</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'job_card_no'" :direction="$sortDirection" wire:click="sort('job_card_no')">Job Card</flux:table.column>
            <flux:table.column class="w-44" sortable :sorted="$sortBy === 'registration_no'" :direction="$sortDirection" wire:click="sort('registration_no')">Vehicle</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'department'" :direction="$sortDirection" wire:click="sort('department')">Department</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'service_type'" :direction="$sortDirection" wire:click="sort('service_type')">Service Type</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'advisor'" :direction="$sortDirection" wire:click="sort('advisor')">Advisor</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'floor_incharge'" :direction="$sortDirection" wire:click="sort('floor_incharge')">Floor In-charge</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'technician'" :direction="$sortDirection" wire:click="sort('technician')">Technician</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'bay'" :direction="$sortDirection" wire:click="sort('bay')">Bay</flux:table.column>
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'template'" :direction="$sortDirection" wire:click="sort('template')">Template</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'started_at'" :direction="$sortDirection" wire:click="sort('started_at')">Work Start</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'completed_at'" :direction="$sortDirection" wire:click="sort('completed_at')">Work Complete</flux:table.column>
            <flux:table.column class="w-24" align="end" sortable :sorted="$sortBy === 'tat_seconds'" :direction="$sortDirection" wire:click="sort('tat_seconds')">TAT</flux:table.column>
            <flux:table.column class="w-20 text-center" sortable :sorted="$sortBy === 'items_count'" :direction="$sortDirection" wire:click="sort('items_count')">Items</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->inspection_no ?? '—' }}</flux:table.cell>

                    {{-- When the sheet was raised, distinct from when a
                         technician picked it up — the gap is the queue. --}}
                    <flux:table.cell class="text-sm whitespace-nowrap">
                        <div>{{ $row->created_at?->format('d/m/Y') }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->created_at?->format('h:i A') }}</div>
                    </flux:table.cell>

                    <flux:table.cell class="font-mono text-xs">{{ $row->jobCard?->job_card_no ?? '—' }}</flux:table.cell>

                    <flux:table.cell class="text-sm">
                        <div class="font-mono">{{ $row->jobCard?->customerVehicle?->registration_no ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->jobCard?->customerVehicle?->model?->name ?? '—' }}</div>
                    </flux:table.cell>

                    <flux:table.cell class="text-sm">{{ $row->jobCard?->workshopDepartment?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->jobCard?->serviceType?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->advisor?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->floorIncharge?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->technician?->name ?? '— unassigned —' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->bay?->name ?? '—' }}</flux:table.cell>

                    <flux:table.cell class="text-sm">
                        <div>{{ $row->template?->name ?? '—' }}</div>
                        @if ($row->template?->applies_to)
                            <div class="text-xs text-zinc-500 mt-0.5 uppercase">{{ $row->template->applies_to }}</div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="text-sm whitespace-nowrap">
                        @if ($row->started_at)
                            <div>{{ $row->started_at->format('d/m/Y') }}</div>
                            <div class="text-xs text-zinc-500 mt-0.5">{{ $row->started_at->format('h:i A') }}</div>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="text-sm whitespace-nowrap">
                        @if ($row->completed_at)
                            <div>{{ $row->completed_at->format('d/m/Y') }}</div>
                            <div class="text-xs text-zinc-500 mt-0.5">{{ $row->completed_at->format('h:i A') }}</div>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell align="end" class="font-mono text-sm tabular-nums">
                        @if ($row->tat_seconds)
                            {{ intdiv((int) $row->tat_seconds, 3600) }}h {{ intdiv((int) $row->tat_seconds % 3600, 60) }}m
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="text-center font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell>
                        @php($statusColor = match ($row->status) {
                            'pending' => 'amber', 'wip' => 'blue', 'completed' => 'lime',
                            'approved' => 'green', 'rejected' => 'red', 'cancelled' => 'zinc',
                            default => 'zinc',
                        })
                        <flux:badge :color="$statusColor" size="sm">{{ $allStatuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('digital_inspection.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('digital-inspection.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('digital_inspection.delete')
                                <flux:modal.trigger :name="'di-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'di-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->inspection_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Cascades to inspection items.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('di-delete-{{ $row->id }}').close()">Delete</flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="17" class="text-center text-zinc-500 py-12">
                        <flux:icon.magnifying-glass-circle class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No inspections yet</div>
                        <flux:text class="mt-1">Open one from a job card to start the technician's checklist.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
