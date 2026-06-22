<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Technician Findings</flux:heading>
            <flux:text class="mt-1">Additional work discovered mid-job — extra spares/labour, routed for approval.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('technician_finding.create')
                <flux:button variant="primary" icon="plus" :href="route('technician-finding.create')" wire:navigate>New Finding</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by finding no, description, JC no..." icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="typeFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All types</flux:select.option>
            @foreach ($types as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $typeFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'finding_no'" :direction="$sortDirection" wire:click="sort('finding_no')">No.</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column class="w-32">Job Card / VIO</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'finding_type'" :direction="$sortDirection" wire:click="sort('finding_type')">Type</flux:table.column>
            <flux:table.column class="w-28 text-right">Est. Amount</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->finding_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium text-sm">{{ $row->description }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">
                            {{ $row->finding_type === 'spare' ? ($row->spare?->name ?? '—') : ($row->labour?->name ?? '—') }}
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div class="font-mono">{{ $row->jobCard?->job_card_no ?? '—' }}</div>
                        @if ($row->order)
                            <div class="text-xs text-zinc-500 mt-0.5 font-mono">{{ $row->order->order_no }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$row->finding_type === 'labour' ? 'purple' : 'sky'" size="sm">{{ $types[$row->finding_type] ?? $row->finding_type }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm">{{ $row->estimated_amount !== null ? number_format((float) $row->estimated_amount, 2) : '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @php($statusColor = match ($row->status) {
                            'recommended' => 'amber', 'approved' => 'lime', 'rejected' => 'red', 'converted' => 'blue',
                            default => 'zinc',
                        })
                        <flux:badge :color="$statusColor" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('technician_finding.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('technician-finding.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('technician_finding.delete')
                                <flux:modal.trigger :name="'tf-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'tf-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->finding_no }}?</flux:heading>
                                        <flux:text>Cannot be undone.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('tf-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.wrench-screwdriver class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No findings yet</div>
                        <flux:text class="mt-1">Log additional work a technician discovers during a job.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
