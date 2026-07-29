<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Consumable Approval</flux:heading>
            <flux:text class="mt-1">Approve consumable / labour loss and damage against a job card.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('consumable_approval.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Consumable Report</flux:button>
            @endcan
            @can('consumable_approval.create')
                <flux:button variant="primary" icon="plus" :href="route('consumable-approval.create')" wire:navigate>New Request</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-4 grid grid-cols-2 md:grid-cols-5 gap-3">
        @foreach ([
            ['label' => 'Pending', 'value' => $kpis['pending'], 'icon' => 'clock', 'filter' => 'requested'],
            ['label' => 'Approved', 'value' => $kpis['approved'], 'icon' => 'check-circle', 'filter' => 'approved'],
            ['label' => 'Rejected', 'value' => $kpis['rejected'], 'icon' => 'x-circle', 'filter' => 'rejected'],
            ['label' => 'Expired Issued', 'value' => $kpis['expired'], 'icon' => 'calendar-days', 'filter' => null],
            ['label' => 'Damaged Issued', 'value' => $kpis['damaged'], 'icon' => 'exclamation-triangle', 'filter' => null],
        ] as $kpi)
            <button type="button" @if ($kpi['filter']) wire:click="$set('statusFilter', '{{ $kpi['filter'] }}')" @else disabled @endif
                class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 @if ($kpi['filter']) hover:border-zinc-400 dark:hover:border-zinc-500 transition @endif">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpi['value'] }}</div>
            </button>
        @endforeach
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.paint-brush class="size-4" /><flux:text size="sm">Paint Consumption</flux:text></div>
            <div class="mt-1 text-xl font-semibold tabular-nums">₹{{ number_format($kpis['paint'], 0) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.sparkles class="size-4" /><flux:text size="sm">VA Consumption</flux:text></div>
            <div class="mt-1 text-xl font-semibold tabular-nums">₹{{ number_format($kpis['va'], 0) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.banknotes class="size-4" /><flux:text size="sm">This Month's Consumption</flux:text></div>
            <div class="mt-1 text-xl font-semibold tabular-nums">₹{{ number_format($kpis['monthly'], 0) }}</div>
        </div>
    </div>

    @if ($byDepartment->isNotEmpty())
        <div class="mb-6 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <flux:text size="sm" class="font-medium text-zinc-500 mb-2">Department-wise Consumption</flux:text>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1">
                @foreach ($byDepartment as $d)
                    <div class="flex items-center justify-between text-sm py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span>{{ $d->department }}</span>
                        <span class="font-mono text-zinc-500">₹{{ number_format((float) $d->total, 0) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search request / job card…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="categoryFilter" variant="listbox" class="max-w-48">
            <flux:select.option value="all">All categories</flux:select.option>
            @foreach ($categories as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $categoryFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'request_no'" :direction="$sortDirection" wire:click="sort('request_no')">Request</flux:table.column>
            <flux:table.column>Job Card / Category</flux:table.column>
            <flux:table.column class="w-20 text-end">Lines</flux:table.column>
            <flux:table.column class="w-36">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->request_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div class="font-mono">{{ $row->jobCard?->job_card_no ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $categories[$row->consumable_category] ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'approved' => 'lime',
                            'under_review', 'requested' => 'amber',
                            'on_hold' => 'orange',
                            'rejected' => 'red',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('consumable_approval.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('consumable-approval.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('consumable_approval.delete')
                                <flux:modal.trigger :name="'ca-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'ca-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->request_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Lines and attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('ca-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.beaker class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No consumable requests yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
