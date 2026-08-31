<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Technician TAT</flux:heading>
            <flux:text class="mt-1">
                How long each technician takes on a particular job, line by line. Time is their running clock —
                pauses they gave a reason for are excluded.
            </flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" icon="clock" :href="route('inspection-order-history.index')" wire:navigate>VIO History</flux:button>
            @can('inspection_order_history.export')
                <flux:button variant="outline" icon="arrow-down-tray" wire:click="download">Export CSV</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3">
        <div class="flex-1 min-w-0">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by job — clutch, oil change, alignment…"
                icon="magnifying-glass" clearable class="w-full" />
        </div>

        @php($filtersOn = $departmentFilter !== 'all' || $serviceTypeFilter !== 'all' || $technicianFilter !== 'all'
            || $fromDate || $toDate || ! $completedOnly)
        <flux:dropdown class="shrink-0">
            <flux:button icon="funnel" variant="{{ $filtersOn ? 'primary' : 'outline' }}">Filters</flux:button>

            <flux:popover class="w-80 space-y-4">
                <flux:select wire:model.live="departmentFilter" variant="listbox" searchable label="Department">
                    <flux:select.option value="all">All departments</flux:select.option>
                    @foreach ($this->departments as $d)
                        <flux:select.option :value="(string) $d->id" wire:key="tdept-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="serviceTypeFilter" variant="listbox" searchable label="Service Type">
                    <flux:select.option value="all">All service types</flux:select.option>
                    @foreach ($this->serviceTypes as $st)
                        <flux:select.option :value="(string) $st->id" wire:key="tst-{{ $st->id }}">{{ $st->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="technicianFilter" variant="listbox" searchable label="Technician">
                    <flux:select.option value="all">All technicians</flux:select.option>
                    @foreach ($this->technicians as $t)
                        <flux:select.option :value="(string) $t->id" wire:key="ttech-{{ $t->id }}">{{ $t->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:separator variant="subtle" />

                <div class="grid grid-cols-2 gap-2">
                    <flux:date-picker locale="en-IN" wire:model.live="fromDate" placeholder="From" with-today selectable-header fixed-weeks type="input" clearable />
                    <flux:date-picker locale="en-IN" wire:model.live="toDate" placeholder="To" with-today selectable-header fixed-weeks type="input" clearable />
                </div>

                <flux:switch wire:model.live="completedOnly" label="Completed lines only"
                    description="Unfinished work has no turnaround to compare." />
            </flux:popover>
        </flux:dropdown>

        @if ($search || $filtersOn)
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters" class="shrink-0">Clear</flux:button>
        @endif
    </div>

    <div class="overflow-x-auto">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'job'" :direction="$sortDirection" wire:click="sort('job')">Job</flux:table.column>
                <flux:table.column class="w-44" sortable :sorted="$sortBy === 'technician'" :direction="$sortDirection" wire:click="sort('technician')">Technician</flux:table.column>
                <flux:table.column class="w-24 text-center" sortable :sorted="$sortBy === 'jobs_done'" :direction="$sortDirection" wire:click="sort('jobs_done')">Done</flux:table.column>
                <flux:table.column class="w-28" align="end" sortable :sorted="$sortBy === 'avg_seconds'" :direction="$sortDirection" wire:click="sort('avg_seconds')">Average</flux:table.column>
                <flux:table.column class="w-28" align="end" sortable :sorted="$sortBy === 'min_seconds'" :direction="$sortDirection" wire:click="sort('min_seconds')">Fastest</flux:table.column>
                <flux:table.column class="w-28" align="end" sortable :sorted="$sortBy === 'max_seconds'" :direction="$sortDirection" wire:click="sort('max_seconds')">Slowest</flux:table.column>
                <flux:table.column class="w-28" align="end" sortable :sorted="$sortBy === 'total_seconds'" :direction="$sortDirection" wire:click="sort('total_seconds')">Total</flux:table.column>
                <flux:table.column class="w-36" align="end">vs. shop average</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->rows as $r)
                    <flux:table.row wire:key="tat-{{ md5($r->job.'|'.$r->technician) }}">
                        <flux:table.cell class="text-sm font-medium">{{ $r->job }}</flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $r->technician }}</flux:table.cell>
                        <flux:table.cell class="text-center font-mono text-sm">{{ $r->jobs_done }}</flux:table.cell>
                        <flux:table.cell class="text-end font-mono text-sm">{{ \App\Modules\InspectionOrderHistory\Livewire\TechnicianTat::humanise($r->avg_seconds) }}</flux:table.cell>
                        <flux:table.cell class="text-end font-mono text-sm text-zinc-500">{{ \App\Modules\InspectionOrderHistory\Livewire\TechnicianTat::humanise((float) $r->min_seconds) }}</flux:table.cell>
                        <flux:table.cell class="text-end font-mono text-sm text-zinc-500">{{ \App\Modules\InspectionOrderHistory\Livewire\TechnicianTat::humanise((float) $r->max_seconds) }}</flux:table.cell>
                        <flux:table.cell class="text-end font-mono text-sm text-zinc-500">{{ \App\Modules\InspectionOrderHistory\Livewire\TechnicianTat::humanise((float) $r->total_seconds) }}</flux:table.cell>
                        <flux:table.cell align="end">
                            @if ($r->delta_seconds === null || abs($r->delta_seconds) < 60)
                                <flux:badge size="sm" color="zinc">on par</flux:badge>
                            @elseif ($r->delta_seconds < 0)
                                <flux:badge size="sm" color="lime">{{ \App\Modules\InspectionOrderHistory\Livewire\TechnicianTat::humanise(abs($r->delta_seconds)) }} faster</flux:badge>
                            @else
                                <flux:badge size="sm" color="amber">{{ \App\Modules\InspectionOrderHistory\Livewire\TechnicianTat::humanise($r->delta_seconds) }} slower</flux:badge>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center text-zinc-500 py-12">
                            <flux:icon.chart-bar class="mx-auto mb-3 size-8 text-zinc-400" />
                            <div class="font-medium">Nothing to compare yet</div>
                            <flux:text class="mt-1">Turnaround appears once technicians have run their timers on the bench.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
