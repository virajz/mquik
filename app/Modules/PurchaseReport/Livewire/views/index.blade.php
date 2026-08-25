<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Purchase Report</flux:heading>
            <flux:text class="mt-1">Every vendor RFQ — vendor, type, parts, payment terms and status.</flux:text>
        </div>
        @can('purchase_report.export')
            <flux:button variant="primary" icon="arrow-down-tray" wire:click="download">Export CSV</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search RFQ no or job card…" icon="magnifying-glass" clearable class="max-w-sm" />

        <flux:select wire:model.live="statusFilter" variant="listbox" clearable placeholder="All statuses" class="max-w-44">
            <flux:select.option value="all">All statuses</flux:select.option>
            @foreach ($this->statuses as $key => $label)
                <flux:select.option :value="$key" wire:key="st-{{ $key }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="typeFilter" variant="listbox" clearable placeholder="All types" class="max-w-56">
            <flux:select.option value="all">All types</flux:select.option>
            @foreach ($this->inquiryTypes as $key => $label)
                <flux:select.option :value="$key" wire:key="ty-{{ $key }}">{{ $label }}</flux:select.option>
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

        <flux:date-picker locale="en-IN" wire:model.live="fromDate" class="max-w-40" with-today selectable-header fixed-weeks type="input" />
        <flux:date-picker locale="en-IN" wire:model.live="toDate" class="max-w-40" with-today selectable-header fixed-weeks type="input" />

        @if ($search || $statusFilter !== 'all' || $typeFilter !== 'all' || $vendorFilter !== 'all' || $fromDate || $toDate)
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28">RFQ No</flux:table.column>
            <flux:table.column>Vendor</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column class="w-28">Job Card</flux:table.column>
            <flux:table.column class="w-20 text-end">Parts</flux:table.column>
            <flux:table.column class="w-40">Payment Terms</flux:table.column>
            <flux:table.column class="w-40">Status</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($paginator as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->vpi_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->vendor?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $this->inquiryTypes[$row->inquiry_type] ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $row->jobCard?->job_card_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $this->paymentTerms[$row->payment_term] ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'completed' => 'lime',
                            'cancelled' => 'red',
                            'in_progress' => 'blue',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $this->statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500 py-12">
                        <flux:icon.document-chart-bar class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No RFQs match these filters</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($paginator->hasPages())<div class="mt-4"><flux:pagination :paginator="$paginator" /></div>@endif
</div>
