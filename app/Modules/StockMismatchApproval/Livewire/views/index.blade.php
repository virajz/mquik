<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Stock Mismatch Approval</flux:heading>
            <flux:text class="mt-1">Request / response approval for stock-count variances.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('stock_mismatch_approval.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Stock Mismatch Approval Report</flux:button>
            @endcan
            @can('stock_mismatch_approval.create')
                <flux:button variant="primary" icon="plus" :href="route('stock-mismatch-approval.create')" wire:navigate>New Approval</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ([
            ['label' => 'Requested', 'value' => $kpis['requested'], 'icon' => 'clock'],
            ['label' => 'Under Review', 'value' => $kpis['under_review'], 'icon' => 'magnifying-glass'],
            ['label' => 'Approved', 'value' => $kpis['approved'], 'icon' => 'check-circle'],
            ['label' => 'Rejected', 'value' => $kpis['rejected'], 'icon' => 'x-circle', 'danger' => true],
        ] as $kpi)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-2xl font-semibold tabular-nums @if (($kpi['danger'] ?? false) && $kpi['value'] > 0) text-red-600 dark:text-red-400 @endif">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search approval / count…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-52">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'approval_no'" :direction="$sortDirection" wire:click="sort('approval_no')">Approval</flux:table.column>
            <flux:table.column>Stock Count / Request</flux:table.column>
            <flux:table.column class="w-44">Variance Reason</flux:table.column>
            <flux:table.column class="w-40" sortable :sorted="$sortBy === 'approval_status'" :direction="$sortDirection" wire:click="sort('approval_status')">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->approval_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div class="font-mono">{{ $row->stockCount?->count_no ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->requestedBy?->name ?? '—' }} @if ($row->requestedTo)→ {{ $row->requestedTo->name }}@endif</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">{{ \App\Modules\StockMismatchApproval\Models\StockMismatchApproval::varianceReasons()[$row->variance_reason] ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->approval_status) {
                            'approved' => 'lime',
                            'under_review' => 'sky',
                            'requested' => 'amber',
                            'rejected' => 'red',
                            'cancelled' => 'zinc',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->approval_status] ?? $row->approval_status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('stock_mismatch_approval.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('stock-mismatch-approval.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('stock_mismatch_approval.delete')
                                <flux:modal.trigger :name="'sma-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'sma-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->approval_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('sma-delete-{{ $row->id }}').close()">Delete</flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-500 py-12">
                        <flux:icon.clipboard-document-check class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No stock mismatch approvals yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
