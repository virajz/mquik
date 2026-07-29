<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Sales Inquiries</flux:heading>
            <flux:text class="mt-1">Capture a service / parts inquiry, assign, follow up and track to conversion.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('sales_inquiry.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Sales Inquiry Report</flux:button>
            @endcan
            @can('sales_inquiry.create')
                <flux:button variant="primary" icon="plus" :href="route('sales-inquiry.create')" wire:navigate>New Inquiry</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach ([
            ['label' => 'New Today', 'value' => $kpis['new_today'], 'icon' => 'inbox-arrow-down'],
            ['label' => 'Open', 'value' => $kpis['open'], 'icon' => 'clock'],
            ['label' => 'Quotations Sent', 'value' => $kpis['quotations'], 'icon' => 'document-text'],
            ['label' => 'Converted', 'value' => $kpis['converted'], 'icon' => 'check-circle'],
            ['label' => 'Lost', 'value' => $kpis['lost'], 'icon' => 'x-circle', 'danger' => true],
            ['label' => 'Conversion', 'value' => $kpis['conversion_rate'].'%', 'icon' => 'chart-bar'],
        ] as $kpi)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-2xl font-semibold tabular-nums @if (($kpi['danger'] ?? false) && (int) $kpi['value'] > 0) text-red-600 dark:text-red-400 @endif">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="mb-6 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex items-center justify-between">
            <flux:text size="sm" class="font-medium text-zinc-500">Revenue Generated (converted)</flux:text>
            <flux:text class="text-xl font-semibold tabular-nums">₹{{ number_format($kpis['revenue'], 0) }}</flux:text>
        </div>
        @if ($bySource->isNotEmpty())
            <flux:separator class="my-3" />
            <flux:text size="sm" class="font-medium text-zinc-500 mb-2">Source-wise Inquiries</flux:text>
            <div class="flex flex-wrap gap-2">
                @foreach ($bySource as $s)
                    <flux:badge color="zinc" size="sm">{{ $sources[$s->inquiry_source] ?? $s->inquiry_source }}: {{ $s->total }}</flux:badge>
                @endforeach
            </div>
        @endif
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search inquiry no…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-52">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="sourceFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All sources</flux:select.option>
            @foreach ($sources as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $sourceFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'inquiry_no'" :direction="$sortDirection" wire:click="sort('inquiry_no')">Inquiry</flux:table.column>
            <flux:table.column>Customer / Assigned</flux:table.column>
            <flux:table.column class="w-28 text-end">Est. Value</flux:table.column>
            <flux:table.column class="w-44">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->inquiry_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ trim(($row->customer?->first_name ?? '').' '.($row->customer?->last_name ?? '')) ?: '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->assignedTo?->name ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->estimated_value !== null ? number_format((float) $row->estimated_value, 0) : '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'converted' => 'lime',
                            'appointment_booked', 'quotation_sent' => 'blue',
                            'assigned' => 'sky',
                            'pending' => 'amber',
                            'lost_opportunity' => 'red',
                            'cancelled' => 'zinc',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('sales_inquiry.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('sales-inquiry.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('sales_inquiry.delete')
                                <flux:modal.trigger :name="'si-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'si-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->inquiry_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('si-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.phone-arrow-down-left class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No inquiries yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
