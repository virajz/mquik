<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Advance Receipt Request</flux:heading>
            <flux:text class="mt-1">Advance payment requests to customers — purpose, amount and status.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('advance_receipt_request.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Report</flux:button>
            @endcan
            @can('advance_receipt_request.create')
                <flux:button variant="primary" icon="plus" :href="route('advance-receipt-request.create')" wire:navigate>New Request</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ([
            ['label' => 'Requested', 'value' => $kpis['requested'], 'icon' => 'paper-airplane', 'filter' => 'requested'],
            ['label' => 'Partially Paid', 'value' => $kpis['partially_paid'], 'icon' => 'banknotes', 'filter' => 'partially_paid'],
            ['label' => 'Fully Paid', 'value' => $kpis['fully_paid'], 'icon' => 'check-circle', 'filter' => 'fully_paid'],
            ['label' => 'Closed', 'value' => $kpis['closed'], 'icon' => 'x-circle', 'filter' => 'cancelled'],
        ] as $kpi)
            <button type="button" wire:click="$set('statusFilter', '{{ $kpi['filter'] }}')"
                class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
                <div class="flex items-center gap-2 text-zinc-500">
                    <flux:icon :name="$kpi['icon']" class="size-4" />
                    <flux:text size="sm">{{ $kpi['label'] }}</flux:text>
                </div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpi['value'] }}</div>
            </button>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search request / JC no…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="purposeFilter" variant="listbox" class="max-w-56">
            <flux:select.option value="all">All purposes</flux:select.option>
            @foreach ($purposes as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $purposeFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'request_no'" :direction="$sortDirection" wire:click="sort('request_no')">Request No</flux:table.column>
            <flux:table.column class="w-28">Job Card</flux:table.column>
            <flux:table.column>Customer</flux:table.column>
            <flux:table.column>Purpose</flux:table.column>
            <flux:table.column class="w-32 text-end" sortable :sorted="$sortBy === 'amount'" :direction="$sortDirection" wire:click="sort('amount')">Amount</flux:table.column>
            <flux:table.column class="w-36">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->request_no }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $row->jobCard?->job_card_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->customer?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $purposes[$row->advance_purpose] ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->amount !== null ? '₹ '.number_format((float) $row->amount, 2) : '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->payment_status) {
                            'fully_paid' => 'lime',
                            'partially_paid' => 'blue',
                            'cancelled', 'failed', 'rejected' => 'red',
                            'refunded' => 'purple',
                            default => 'amber',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->payment_status] ?? $row->payment_status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('advance_receipt_request.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('advance-receipt-request.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('advance_receipt_request.delete')
                                <flux:modal.trigger :name="'arr-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'arr-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->request_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('arr-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.banknotes class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No advance requests yet</div>
                        <flux:text class="mt-1">Request an advance payment from a customer.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
