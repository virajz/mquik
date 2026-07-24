<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Tyre Reports</flux:heading>
            <flux:text class="mt-1">Five-wheel tyre inspection — size, pressure, tread and replace recommendations.</flux:text>
        </div>
        @can('tyre_report.create')
            <flux:button variant="primary" icon="plus" :href="route('tyre-report.create')" wire:navigate>New Tyre Report</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by report no, customer or reg no…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:input type="date" wire:model.live="dateFrom" class="max-w-40" />
        <flux:input type="date" wire:model.live="dateTo" class="max-w-40" />
        @if ($search || $dateFrom || $dateTo)
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'report_no'" :direction="$sortDirection" wire:click="sort('report_no')">No.</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'reported_on'" :direction="$sortDirection" wire:click="sort('reported_on')">Date</flux:table.column>
            <flux:table.column>Customer / Vehicle</flux:table.column>
            <flux:table.column class="w-40">Inspector</flux:table.column>
            <flux:table.column class="w-28">To Replace</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->report_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->reported_on?->format('d M Y') ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium">{{ trim($row->customer?->first_name.' '.($row->customer?->last_name ?? '')) ?: '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">
                            @if ($row->customerVehicle)<span class="font-mono">{{ $row->customerVehicle->registration_no }}</span>@endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $row->inspectedBy?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($row->replace_count > 0)
                            <flux:badge color="red" size="sm">{{ $row->replace_count }} tyre{{ $row->replace_count === 1 ? '' : 's' }}</flux:badge>
                        @else
                            <flux:badge color="lime" size="sm">None</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('tyre_report.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('tyre-report.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('tyre_report.delete')
                                <flux:modal.trigger :name="'tr-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'tr-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->report_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Removes the report and its five wheel lines.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('tr-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.lifebuoy class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No tyre reports yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
