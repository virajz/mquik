<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Goods Handover / Parts Return</flux:heading>
            <flux:text class="mt-1">Hand received parts to a technician and capture technician parts returns.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('goods_handover.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Purchase Report</flux:button>
            @endcan
            @can('goods_handover.create')
                <flux:button variant="primary" icon="plus" :href="route('goods-handover.create')" wire:navigate>New Handover</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach ([
            ['label' => 'Materials Issued Today', 'value' => $kpis['issued_today'], 'icon' => 'arrows-right-left'],
            ['label' => 'Material Returns', 'value' => $kpis['returns'], 'icon' => 'arrow-uturn-left'],
            ['label' => 'Technicians Served', 'value' => $kpis['technicians'], 'icon' => 'users'],
        ] as $kpi)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    @if ($consumption->isNotEmpty())
        <div class="mb-6 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <flux:text size="sm" class="font-medium text-zinc-500 mb-2">Technician-wise Consumption</flux:text>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1">
                @foreach ($consumption as $c)
                    <div class="flex items-center justify-between text-sm py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span>{{ $c->technician }}</span>
                        <span class="font-mono text-zinc-500">issued {{ rtrim(rtrim(number_format((float) $c->issued, 2), '0'), '.') }} · returned {{ rtrim(rtrim(number_format((float) $c->returned, 2), '0'), '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search handover / job card…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-56">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'handover_no'" :direction="$sortDirection" wire:click="sort('handover_no')">Handover</flux:table.column>
            <flux:table.column>Technician / Job Card</flux:table.column>
            <flux:table.column class="w-20 text-end">Lines</flux:table.column>
            <flux:table.column class="w-44">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->handover_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $row->receivedBy?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 font-mono">{{ $row->jobCard?->job_card_no ?? $row->goodsReceipt?->grn_no ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'verified' => 'lime',
                            'received' => 'sky',
                            'mismatch_accepted' => 'amber',
                            'mismatch_rejected' => 'red',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('goods_handover.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('goods-handover.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('goods_handover.delete')
                                <flux:modal.trigger :name="'gho-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'gho-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->handover_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Lines and attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('gho-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.arrows-right-left class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No handovers yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
