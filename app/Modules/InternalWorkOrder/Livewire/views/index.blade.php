<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Internal Work Order</flux:heading>
            <flux:text class="mt-1">Centralized register for internal complaints, requests and work orders — with TAT tracking.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('internal_work_order.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">IWO Register Report</flux:button>
            @endcan
            @can('internal_work_order.create')
                <flux:button variant="primary" icon="plus" :href="route('internal-work-order.create')" wire:navigate>New IWO</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach ([
            ['label' => 'Total', 'value' => $kpis['total'], 'icon' => 'clipboard-document-list'],
            ['label' => 'Open', 'value' => $kpis['open'], 'icon' => 'inbox'],
            ['label' => 'Assigned', 'value' => $kpis['assigned'], 'icon' => 'user-circle'],
            ['label' => 'Overdue', 'value' => $kpis['overdue'], 'icon' => 'clock', 'danger' => true],
            ['label' => 'Resolved Today', 'value' => $kpis['resolved_today'], 'icon' => 'check-circle'],
            ['label' => 'Avg TAT (hrs)', 'value' => $kpis['avg_tat_hours'], 'icon' => 'chart-bar'],
        ] as $kpi)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-2xl font-semibold tabular-nums @if (($kpi['danger'] ?? false) && $kpi['value'] > 0) text-red-600 dark:text-red-400 @endif">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search IWO no / title…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="priorityFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All priority</flux:select.option>
            @foreach ($priorities as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $priorityFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'iwo_no'" :direction="$sortDirection" wire:click="sort('iwo_no')">IWO No</flux:table.column>
            <flux:table.column>Title / Requester</flux:table.column>
            <flux:table.column class="w-40">Category</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'priority'" :direction="$sortDirection" wire:click="sort('priority')">Priority</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->iwo_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div class="font-medium">{{ $row->title ?: '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->requestedBy?->name ?? '—' }} @if ($row->assignedTo)→ {{ $row->assignedTo->name }}@endif</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">{{ \App\Modules\InternalWorkOrder\Models\InternalWorkOrder::categories()[$row->iwo_category] ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @php($pc = match ($row->priority) { 'high' => 'red', 'medium' => 'amber', default => 'zinc' })
                        <flux:badge :color="$pc" size="sm">{{ \App\Modules\InternalWorkOrder\Models\InternalWorkOrder::priorities()[$row->priority] ?? $row->priority }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'resolved' => 'lime',
                            'in_progress' => 'sky',
                            'under_review' => 'blue',
                            'requested' => 'amber',
                            'on_hold' => 'orange',
                            'cancelled' => 'red',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('internal_work_order.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('internal-work-order.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('internal_work_order.delete')
                                <flux:modal.trigger :name="'iwo-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'iwo-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->iwo_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('iwo-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.clipboard-document-list class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No internal work orders yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
