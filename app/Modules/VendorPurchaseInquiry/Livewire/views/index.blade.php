<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Vendor Purchase Inquiries</flux:heading>
            <flux:text class="mt-1">RFQs to vendors — part rate, brand, delivery time, warranty and payment terms.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('vendor_purchase_inquiry.create')
                <flux:button variant="primary" icon="plus" :href="route('vendor-purchase-inquiry.create')" wire:navigate>New RFQ</flux:button>
            @endcan
        </div>
    </div>

    {{-- Dashboard counters --}}
    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ([
            ['label' => 'Pending', 'value' => $kpis['pending'], 'icon' => 'clock', 'filter' => 'pending'],
            ['label' => 'In Progress', 'value' => $kpis['in_progress'], 'icon' => 'arrow-path', 'filter' => 'in_progress'],
            ['label' => 'Completed', 'value' => $kpis['completed'], 'icon' => 'check-circle', 'filter' => 'completed'],
            ['label' => 'Cancelled', 'value' => $kpis['cancelled'], 'icon' => 'x-circle', 'filter' => 'cancelled'],
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
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by RFQ no or job card…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="typeFilter" variant="listbox" class="max-w-56">
            <flux:select.option value="all">All types</flux:select.option>
            @foreach ($inquiryTypes as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="vendorFilter" variant="listbox" searchable clearable :filter="false" placeholder="All vendors" class="max-w-52">
            <x-slot name="search">
                <flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Search vendor…" />
            </x-slot>
            <flux:select.option value="all">All vendors</flux:select.option>
            @foreach ($this->vendors as $v)
                <flux:select.option :value="(string) $v->id" wire:key="vf-{{ $v->id }}">{{ $v->name }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $typeFilter !== 'all' || $vendorFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'vpi_no'" :direction="$sortDirection" wire:click="sort('vpi_no')">RFQ No</flux:table.column>
            <flux:table.column>Vendor</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column class="w-28">Job Card</flux:table.column>
            <flux:table.column class="w-20 text-end">Parts</flux:table.column>
            <flux:table.column class="w-40">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->vpi_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->vendor?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $inquiryTypes[$row->inquiry_type] ?? '—' }}</div>
                        @if ($row->priority)
                            <div class="text-xs text-zinc-500 mt-0.5">{{ $row->priority->name }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $row->jobCard?->job_card_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'completed' => 'lime',
                            'cancelled' => 'red',
                            'in_progress' => 'blue',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('vendor_purchase_inquiry.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('vendor-purchase-inquiry.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('vendor_purchase_inquiry.delete')
                                <flux:modal.trigger :name="'vpi-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'vpi-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->vpi_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Parts, charges and attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('vpi-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.clipboard-document-list class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No vendor inquiries yet</div>
                        <flux:text class="mt-1">Raise an RFQ to get a vendor's rate, brand and delivery time.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
