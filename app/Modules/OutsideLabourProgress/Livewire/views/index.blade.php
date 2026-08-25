@php($fmt = fn ($m) => $m ? intdiv($m, 60).'h '.($m % 60).'m' : '—')
<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Outside Labour Progress</flux:heading>
            <flux:text class="mt-1">Progress, completion and total hours across outside labour orders.</flux:text>
        </div>
        @can('outside_labour_progress.export')
            <flux:button variant="primary" icon="arrow-down-tray" wire:click="download">Status Report</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search OLO no or job card…" icon="magnifying-glass" clearable class="max-w-sm" />
        <flux:select wire:model.live="statusFilter" variant="listbox" clearable placeholder="All status" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($this->statuses as $key => $label)
                <flux:select.option :value="$key" wire:key="st-{{ $key }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="vendorFilter" variant="listbox" searchable clearable :filter="false" placeholder="All vendors" class="max-w-52">
            <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Search vendor…" /></x-slot>
            <flux:select.option value="all">All vendors</flux:select.option>
            @foreach ($this->vendors as $v)
                <flux:select.option :value="(string) $v->id" wire:key="vf-{{ $v->id }}">{{ $v->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:date-picker locale="en-IN" wire:model.live="fromDate" class="max-w-40" with-today selectable-header fixed-weeks type="input" />
        <flux:date-picker locale="en-IN" wire:model.live="toDate" class="max-w-40" with-today selectable-header fixed-weeks type="input" />
        @if ($search || $statusFilter !== 'all' || $vendorFilter !== 'all' || $fromDate || $toDate)
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28">OLO No</flux:table.column>
            <flux:table.column>Vendor / Type</flux:table.column>
            <flux:table.column class="w-28">Job Card</flux:table.column>
            <flux:table.column class="w-24 text-end">Hours</flux:table.column>
            <flux:table.column class="w-28 text-end">Net TAT</flux:table.column>
            <flux:table.column class="w-36">Status</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($paginator as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->order_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $row->vendor?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->orderType?->name }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $row->jobCard?->job_card_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ rtrim(rtrim(number_format($row->totalItemHours(), 2), '0'), '.') ?: '0' }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $fmt($row->netTatMinutes()) }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'completed' => 'lime',
                            'cancelled' => 'red',
                            'wip' => 'blue',
                            'on_hold' => 'amber',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $this->statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500 py-12">
                        <flux:icon.chart-bar class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No outside labour orders match these filters</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($paginator->hasPages())<div class="mt-4"><flux:pagination :paginator="$paginator" /></div>@endif
</div>
