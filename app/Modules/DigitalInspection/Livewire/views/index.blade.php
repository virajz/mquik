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

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by inspection no, JC no, or reg no..." icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="technicianFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All technicians</flux:select.option>
            @foreach ($this->technicians as $t)
                <flux:select.option :value="(string) $t->id">{{ $t->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="templateFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All templates</flux:select.option>
            @foreach ($this->templates as $t)
                <flux:select.option :value="(string) $t->id">{{ $t->name }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $technicianFilter !== 'all' || $templateFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'inspection_no'" :direction="$sortDirection" wire:click="sort('inspection_no')">No.</flux:table.column>
            <flux:table.column>Job Card / Vehicle</flux:table.column>
            <flux:table.column class="w-40">Template</flux:table.column>
            <flux:table.column class="w-40">Technician</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'started_at'" :direction="$sortDirection" wire:click="sort('started_at')">Started</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'completed_at'" :direction="$sortDirection" wire:click="sort('completed_at')">Completed</flux:table.column>
            <flux:table.column class="w-24" align="end" sortable :sorted="$sortBy === 'tat_seconds'" :direction="$sortDirection" wire:click="sort('tat_seconds')">TAT</flux:table.column>
            <flux:table.column class="w-20 text-center">Items</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->inspection_no ?? '—' }}</flux:table.cell>
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
                        <div>{{ $row->template?->name ?? '—' }}</div>
                        @if ($row->template?->applies_to)
                            <div class="text-xs text-zinc-500 mt-0.5 uppercase">{{ $row->template->applies_to }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->technician?->name ?? '— unassigned —' }}</flux:table.cell>

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
                    <flux:table.cell colspan="10" class="text-center text-zinc-500 py-12">
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
