@php($REPORT = \App\Modules\InspectionReport\Livewire\Index::class)
<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Inspection Reports</flux:heading>
            <flux:text class="mt-1">
                How long sheets take, and the work the workshop has justified but not yet been paid for.
            </flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" icon="clipboard-document-check" :href="route('digital-inspection.index')" wire:navigate>Inspections</flux:button>
            @can('inspection_report.export')
                <flux:button variant="outline" icon="arrow-down-tray" wire:click="download">Export CSV</flux:button>
            @endcan
        </div>
    </div>

    <flux:tab.group>
        <flux:tabs wire:model.live="tab">
            <flux:tab name="tat" icon="clock">Technician TAT</flux:tab>
            <flux:tab name="future" icon="calendar-days">Future Jobs (FA)</flux:tab>
        </flux:tabs>

        <div class="mt-4 mb-4 flex flex-wrap items-end gap-3">
            <flux:select wire:model.live="technicianFilter" variant="listbox" searchable class="w-52">
                <flux:select.option value="all">All technicians</flux:select.option>
                @foreach ($this->technicians as $t)
                    <flux:select.option :value="(string) $t->id" wire:key="rt-{{ $t->id }}">{{ $t->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="departmentFilter" variant="listbox" searchable class="w-48">
                <flux:select.option value="all">All departments</flux:select.option>
                @foreach ($this->departments as $d)
                    <flux:select.option :value="(string) $d->id" wire:key="rd-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="serviceTypeFilter" variant="listbox" searchable class="w-48">
                <flux:select.option value="all">All service types</flux:select.option>
                @foreach ($this->serviceTypes as $st)
                    <flux:select.option :value="(string) $st->id" wire:key="rs-{{ $st->id }}">{{ $st->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:date-picker locale="en-IN" wire:model.live="dateFrom" placeholder="From" with-today selectable-header fixed-weeks type="input" clearable class="w-44" />
            <flux:date-picker locale="en-IN" wire:model.live="dateTo" placeholder="To" with-today selectable-header fixed-weeks type="input" clearable class="w-44" />

            @if ($dateFrom || $dateTo || $technicianFilter !== 'all' || $departmentFilter !== 'all' || $serviceTypeFilter !== 'all')
                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
            @endif
        </div>

        {{-- TAT, day by day and technician by technician. --}}
        <flux:tab.panel name="tat">
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-32" sortable :sorted="$sortBy === 'day'" :direction="$sortDirection" wire:click="sort('day')">Date</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'technician'" :direction="$sortDirection" wire:click="sort('technician')">Technician</flux:table.column>
                        <flux:table.column class="w-24 text-center" sortable :sorted="$sortBy === 'sheets'" :direction="$sortDirection" wire:click="sort('sheets')">Sheets</flux:table.column>
                        <flux:table.column class="w-28" align="end" sortable :sorted="$sortBy === 'avg_seconds'" :direction="$sortDirection" wire:click="sort('avg_seconds')">Average</flux:table.column>
                        <flux:table.column class="w-28" align="end" sortable :sorted="$sortBy === 'min_seconds'" :direction="$sortDirection" wire:click="sort('min_seconds')">Fastest</flux:table.column>
                        <flux:table.column class="w-28" align="end" sortable :sorted="$sortBy === 'max_seconds'" :direction="$sortDirection" wire:click="sort('max_seconds')">Slowest</flux:table.column>
                        <flux:table.column class="w-28" align="end" sortable :sorted="$sortBy === 'total_seconds'" :direction="$sortDirection" wire:click="sort('total_seconds')">Total</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->tatRows as $r)
                            <flux:table.row wire:key="tat-{{ md5($r->day.'|'.$r->technician) }}">
                                <flux:table.cell class="text-sm whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($r->day)->format('d/m/Y') }}</flux:table.cell>
                                <flux:table.cell class="text-sm">{{ $r->technician }}</flux:table.cell>
                                <flux:table.cell class="text-center font-mono text-sm">{{ $r->sheets }}</flux:table.cell>
                                <flux:table.cell class="text-end font-mono text-sm">{{ $REPORT::humanise((float) $r->avg_seconds) }}</flux:table.cell>
                                <flux:table.cell class="text-end font-mono text-sm text-zinc-500">{{ $REPORT::humanise((float) $r->min_seconds) }}</flux:table.cell>
                                <flux:table.cell class="text-end font-mono text-sm text-zinc-500">{{ $REPORT::humanise((float) $r->max_seconds) }}</flux:table.cell>
                                <flux:table.cell class="text-end font-mono text-sm text-zinc-500">{{ $REPORT::humanise((float) $r->total_seconds) }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="7" class="text-center text-zinc-500 py-12">
                                    <flux:icon.clock class="mx-auto mb-3 size-8 text-zinc-400" />
                                    <div class="font-medium">Nothing to compare yet</div>
                                    <flux:text class="mt-1">Turnaround appears once inspections have been started and completed.</flux:text>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:tab.panel>

        {{-- Work already justified and not yet approved: the follow-up list. --}}
        <flux:tab.panel name="future">
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-28">Date</flux:table.column>
                        <flux:table.column class="w-36">Inspection</flux:table.column>
                        <flux:table.column class="w-44">Vehicle</flux:table.column>
                        <flux:table.column>Checkpoint</flux:table.column>
                        <flux:table.column class="w-32">Recommendation</flux:table.column>
                        <flux:table.column class="w-24">Severity</flux:table.column>
                        <flux:table.column class="w-36">Technician</flux:table.column>
                        <flux:table.column class="w-32">Customer</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->futureRows as $r)
                            <flux:table.row wire:key="fa-{{ $r->id }}">
                                <flux:table.cell class="text-sm whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('d/m/Y') }}</flux:table.cell>
                                <flux:table.cell class="font-mono text-xs">{{ $r->inspection_no }}</flux:table.cell>
                                <flux:table.cell class="text-sm">
                                    <div class="font-mono">{{ $r->registration_no ?? '—' }}</div>
                                    <div class="text-xs text-zinc-500 mt-0.5">{{ $r->model_name ?? '—' }}</div>
                                </flux:table.cell>
                                <flux:table.cell class="text-sm">{{ $r->item_name ?? '—' }}</flux:table.cell>
                                <flux:table.cell class="text-sm">
                                    {{ \App\Modules\DigitalInspection\Models\DigitalInspection::allRecommendations()[$r->recommendation] ?? $r->recommendation }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="match ($r->severity) {
                                        'critical' => 'red', 'high' => 'orange', 'medium' => 'amber', default => 'zinc',
                                    }">{{ ucfirst($r->severity ?? '—') }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-sm">{{ $r->technician }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="match ($r->customer_approval) {
                                        'declined' => 'red', 'deferred' => 'amber', default => 'zinc',
                                    }">{{ $r->customer_approval ? ucfirst($r->customer_approval) : 'Not asked' }}</flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="8" class="text-center text-zinc-500 py-12">
                                    <flux:icon.calendar-days class="mx-auto mb-3 size-8 text-zinc-400" />
                                    <div class="font-medium">No follow-up work outstanding</div>
                                    <flux:text class="mt-1">
                                        Checkpoints marked <span class="font-medium">FA</span> with a repair or replacement recommended appear here
                                        until the customer approves them.
                                    </flux:text>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:tab.panel>
    </flux:tab.group>
</div>
