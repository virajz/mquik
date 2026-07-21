<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Inward / Outward</flux:heading>
            <flux:text class="mt-1">One record per visit — inward opens it, outward closes it, TAT is the gap.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('gate_in_out.create')
                <flux:button variant="primary" icon="plus" wire:click="openCreate">Record Inward</flux:button>
            @endcan
        </div>
    </div>

    {{-- Today's counters --}}
    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ([
            ['label' => 'Inward Today', 'value' => $kpis['inward'], 'icon' => 'arrow-right-end-on-rectangle'],
            ['label' => 'Outward Today', 'value' => $kpis['outward'], 'icon' => 'arrow-left-end-on-rectangle'],
            ['label' => 'Trial Runs Today', 'value' => $kpis['trialRun'], 'icon' => 'bolt'],
            ['label' => 'Still Inside', 'value' => $kpis['inside'], 'icon' => 'building-office'],
        ] as $kpi)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center gap-2 text-zinc-500">
                    <flux:icon :name="$kpi['icon']" class="size-4" />
                    <flux:text size="sm">{{ $kpi['label'] }}</flux:text>
                </div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by reg no, visit no, customer..." icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-36">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="presenceFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">On site + left</flux:select.option>
            <flux:select.option value="inside">Still inside</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="sourceFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All sources</flux:select.option>
            <flux:select.option value="manual">Manual</flux:select.option>
            <flux:select.option value="anpr">ANPR</flux:select.option>
        </flux:select>
        <flux:date-picker wire:model.live="dateFrom" placeholder="From date" with-today selectable-header fixed-weeks type="input" clearable class="max-w-44" />
        <flux:date-picker wire:model.live="dateTo" placeholder="To date" with-today selectable-header fixed-weeks type="input" clearable class="max-w-44" />
        @if ($search || $statusFilter !== 'all' || $presenceFilter !== 'all' || $sourceFilter !== 'all' || $dateFrom || $dateTo)
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'gate_event_no'" :direction="$sortDirection" wire:click="sort('gate_event_no')">No.</flux:table.column>
            <flux:table.column class="w-40" sortable :sorted="$sortBy === 'entered_at'" :direction="$sortDirection" wire:click="sort('entered_at')">In</flux:table.column>
            <flux:table.column class="w-40" sortable :sorted="$sortBy === 'exited_at'" :direction="$sortDirection" wire:click="sort('exited_at')">Out</flux:table.column>
            <flux:table.column class="w-24">TAT</flux:table.column>
            <flux:table.column>Reg. No / Customer</flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->gate_event_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div class="font-medium">{{ $row->entered_at?->format('d M Y') }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">
                            {{ $row->entered_at?->format('h:i A') }}{{ $row->entryGate ? ' · '.$row->entryGate->name : '' }}
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        @if ($row->exited_at)
                            <div class="font-medium">{{ $row->exited_at->format('d M Y') }}</div>
                            <div class="text-xs text-zinc-500 mt-0.5">
                                {{ $row->exited_at->format('h:i A') }}{{ $row->exitGate ? ' · '.$row->exitGate->name : '' }}
                            </div>
                        @else
                            <flux:badge color="amber" size="sm">Still inside</flux:badge>
                            @if ($row->parkingSlot)
                                <div class="text-xs text-zinc-500 mt-0.5">{{ $row->parkingSlot->name }}</div>
                            @endif
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="font-mono text-sm text-zinc-500">{{ $row->tatForHumans() }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-mono font-medium">{{ $row->registration_no }}</div>
                        @if ($row->customer)
                            <div class="text-xs text-zinc-500 mt-0.5">
                                {{ trim($row->customer->first_name.' '.($row->customer->last_name ?? '')) }}
                                @if ($row->customer->phone) · <span class="font-mono">+91 {{ $row->customer->phone }}</span> @endif
                            </div>
                        @else
                            <div class="text-xs text-zinc-500 mt-0.5"><span class="text-amber-600 dark:text-amber-400">Walk-in (unmatched)</span></div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'completed' => 'lime', 'cancelled' => 'zinc', default => 'amber',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                        @if ($row->outward_type)
                            <div class="text-xs text-zinc-500 mt-0.5">
                                {{ \App\Modules\GateInOut\Models\GateInOut::outwardTypes()[$row->outward_type] ?? $row->outward_type }}
                            </div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('gate_in_out.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $row->id }})">Edit</flux:button>
                            @endcan
                            @can('gate_in_out.delete')
                                <flux:modal.trigger :name="'gate-in-out-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'gate-in-out-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->gate_event_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Gate events should rarely be deleted — prefer adding a corrective event.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('gate-in-out-delete-{{ $row->id }}').close()">Delete</flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500 py-12">
                        <flux:icon.arrow-right-end-on-rectangle class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No gate events recorded yet</div>
                        <flux:text class="mt-1">Every vehicle in or out — recorded here.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif

    <livewire:gate-in-out.form />
</div>
