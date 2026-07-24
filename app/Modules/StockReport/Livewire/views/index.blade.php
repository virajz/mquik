<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Stock Report</flux:heading>
            <flux:text class="mt-1">On-hand quantity, value and reorder status per spare.</flux:text>
        </div>
        @can('stock_report.export')
            <flux:button variant="primary" icon="arrow-down-tray" wire:click="download">Export CSV</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search spare name or part no…" icon="magnifying-glass" clearable class="max-w-sm" />

        <flux:select wire:model.live="groupFilter" variant="listbox" searchable clearable :filter="false" placeholder="All groups" class="max-w-52">
            <x-slot name="search">
                <flux:select.search wire:model.live.debounce.250ms="groupSearch" placeholder="Type a group…" />
            </x-slot>
            <flux:select.option value="all">All groups</flux:select.option>
            @foreach ($this->groups as $g)
                <flux:select.option :value="(string) $g->id" wire:key="g-{{ $g->id }}">{{ $g->parent ? $g->parent->name.' › ' : '' }}{{ $g->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="brandFilter" variant="listbox" searchable clearable :filter="false" placeholder="All brands" class="max-w-44">
            <x-slot name="search">
                <flux:select.search wire:model.live.debounce.250ms="brandSearch" placeholder="Type a brand…" />
            </x-slot>
            <flux:select.option value="all">All brands</flux:select.option>
            @foreach ($this->brands as $b)
                <flux:select.option :value="(string) $b->id" wire:key="b-{{ $b->id }}">{{ $b->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="rackFilter" variant="listbox" clearable placeholder="All racks" class="max-w-40">
            <flux:select.option value="all">All racks</flux:select.option>
            @foreach ($this->racks as $r)
                <flux:select.option :value="(string) $r->id" wire:key="rk-{{ $r->id }}">{{ $r->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:checkbox wire:model.live="belowReorderOnly" label="Below reorder only" />

        @if ($search || $groupFilter !== 'all' || $brandFilter !== 'all' || $rackFilter !== 'all' || $partTypeFilter !== 'all' || $belowReorderOnly)
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <div class="mb-3 flex items-center gap-4 text-sm">
        <span class="text-zinc-500">Total stock value (filtered):</span>
        <span class="font-mono font-semibold">₹ {{ number_format($this->totalValue, 2) }}</span>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28">Part No</flux:table.column>
            <flux:table.column>Spare</flux:table.column>
            <flux:table.column class="w-28">Godown</flux:table.column>
            <flux:table.column class="w-24 text-end">On Hand</flux:table.column>
            <flux:table.column class="w-28 text-end">Rate</flux:table.column>
            <flux:table.column class="w-28 text-end">Value</flux:table.column>
            <flux:table.column class="w-24">Status</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $r)
                @php($s = $r['spare'])
                <flux:table.row :key="$s->id">
                    <flux:table.cell class="font-mono text-xs">{{ $s->spare_code ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium">{{ $s->name }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $s->brand?->name }}{{ $s->inventoryGroup ? ' · '.$s->inventoryGroup->name : '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $s->location ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono">{{ rtrim(rtrim(number_format($r['qty'], 2), '0'), '.') }} <span class="text-xs text-zinc-400">{{ $s->uom?->code }}</span></flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">₹ {{ number_format($r['rate'], 2) }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">₹ {{ number_format($r['value'], 2) }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($r['status']) { 'below_min', 'zero', 'negative' => 'red', 'above_max' => 'amber', default => 'lime' })
                        <flux:badge :color="$sc" size="sm">{{ str($r['status'])->headline() }}</flux:badge>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500 py-12">
                        <flux:icon.clipboard-document-list class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No spares match these filters</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($paginator->hasPages())<div class="mt-4"><flux:pagination :paginator="$paginator" /></div>@endif
</div>
