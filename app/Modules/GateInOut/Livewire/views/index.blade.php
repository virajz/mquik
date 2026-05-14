<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Gate In / Out</flux:heading>
            <flux:text class="mt-1">Vehicle entry and exit log — manual or ANPR-captured.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('gate_in_out.create')
                <flux:button variant="primary" icon="plus" wire:click="openCreate">Record Gate Event</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by reg no, event no, customer..." icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="directionFilter" variant="listbox" class="max-w-32">
            <flux:select.option value="all">In + Out</flux:select.option>
            @foreach ($directions as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="sourceFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All sources</flux:select.option>
            <flux:select.option value="manual">Manual</flux:select.option>
            <flux:select.option value="anpr">ANPR</flux:select.option>
        </flux:select>
        <flux:date-picker wire:model.live="dateFrom" placeholder="From date" with-today selectable-header fixed-weeks type="input" clearable class="max-w-44" />
        <flux:date-picker wire:model.live="dateTo" placeholder="To date" with-today selectable-header fixed-weeks type="input" clearable class="max-w-44" />
        @if ($search || $directionFilter !== 'all' || $sourceFilter !== 'all' || $dateFrom || $dateTo)
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'gate_event_no'" :direction="$sortDirection" wire:click="sort('gate_event_no')">No.</flux:table.column>
            <flux:table.column class="w-44" sortable :sorted="$sortBy === 'gated_at'" :direction="$sortDirection" wire:click="sort('gated_at')">When</flux:table.column>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'direction'" :direction="$sortDirection" wire:click="sort('direction')">Dir</flux:table.column>
            <flux:table.column>Reg. No / Customer</flux:table.column>
            <flux:table.column class="w-24">Source</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->gate_event_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div class="font-medium">{{ $row->gated_at?->format('d M Y') }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->gated_at?->format('h:i A') }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$row->direction === 'in' ? 'lime' : 'sky'" size="sm">
                            <flux:icon :name="$row->direction === 'in' ? 'arrow-right-end-on-rectangle' : 'arrow-left-end-on-rectangle'" class="size-3 mr-0.5 inline" />
                            {{ $directions[$row->direction] ?? $row->direction }}
                        </flux:badge>
                    </flux:table.cell>
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
                    <flux:table.cell class="text-sm text-zinc-500">
                        @if ($row->source === 'anpr')
                            <flux:badge color="blue" size="sm">ANPR</flux:badge>
                        @else
                            <span>Manual</span>
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
