<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Inventory</flux:heading>
            <flux:text class="mt-1">Live stock levels, FIFO layers, and MIN/MAX alerts for all spares.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />
                <flux:menu>
                    <flux:menu.item icon="arrow-down-tray"
                        wire:click="$dispatch('start-export', { module: 'Inventory' })">
                        Export
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <livewire:import-export.export-button :module="'Inventory'" wire:key="export-inventory" />

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search spare name, code, location…"
            icon="magnifying-glass"
            clearable
            class="max-w-sm"
        />

        <flux:select wire:model.live="qtyFilter" class="w-44">
            <flux:select.option value="all">All quantities</flux:select.option>
            <flux:select.option value="positive">In stock (&gt;0)</flux:select.option>
            <flux:select.option value="zero">Zero stock</flux:select.option>
            <flux:select.option value="negative">Negative (oversold)</flux:select.option>
            <flux:select.option value="below_min">Below minimum</flux:select.option>
            <flux:select.option value="above_max">Above maximum</flux:select.option>
        </flux:select>

        @if ($search !== '' || $qtyFilter !== 'all' || $groupFilter !== 'all')
            <flux:button variant="ghost" size="sm" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    {{-- Alert summary chips --}}
    @php
        $belowMin = collect($filteredRows)->where('alert', 'below_min')->count();
        $zeroStock = collect($filteredRows)->where('alert', 'zero')->count();
        $negativeStock = collect($filteredRows)->where('alert', 'negative')->count();
    @endphp
    @if ($belowMin || $zeroStock || $negativeStock)
        <div class="mb-4 flex flex-wrap gap-2">
            @if ($belowMin)
                <flux:badge color="yellow" size="sm">{{ $belowMin }} below minimum</flux:badge>
            @endif
            @if ($zeroStock)
                <flux:badge color="orange" size="sm">{{ $zeroStock }} zero stock</flux:badge>
            @endif
            @if ($negativeStock)
                <flux:badge color="red" size="sm">{{ $negativeStock }} negative</flux:badge>
            @endif
        </div>
    @endif

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">
                Spare
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'spare_code'" :direction="$sortDirection" wire:click="sort('spare_code')">
                Code
            </flux:table.column>
            <flux:table.column>Group</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'location'" :direction="$sortDirection" wire:click="sort('location')">
                Location
            </flux:table.column>
            <flux:table.column class="w-28 text-right">Qty on Hand</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'min_qty'" :direction="$sortDirection" wire:click="sort('min_qty')" class="w-20 text-right">
                Min
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'max_qty'" :direction="$sortDirection" wire:click="sort('max_qty')" class="w-20 text-right">
                Max
            </flux:table.column>
            <flux:table.column class="w-32">Status</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($filteredRows as $spare)
                @php
                    $alertColor = match($spare->alert) {
                        'below_min' => 'yellow',
                        'above_max' => 'blue',
                        'zero'      => 'orange',
                        'negative'  => 'red',
                        default     => 'green',
                    };
                    $alertLabel = match($spare->alert) {
                        'below_min' => 'Below Min',
                        'above_max' => 'Above Max',
                        'zero'      => 'Zero',
                        'negative'  => 'Negative',
                        default     => 'OK',
                    };
                    $qtyClass = $spare->current_qty < 0
                        ? 'text-red-600'
                        : ($spare->current_qty == 0 ? 'text-orange-600' : 'text-zinc-800');
                @endphp
                <flux:table.row :key="$spare->id">
                    <flux:table.cell>
                        <div class="font-medium">{{ $spare->name }}</div>
                        @if ($spare->brand)
                            <div class="text-xs text-zinc-500">{{ $spare->brand->name }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="font-mono text-xs text-zinc-500">
                        {{ $spare->spare_code ?? '—' }}
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">
                        {{ $spare->inventoryGroup?->name ?? '—' }}
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">
                        {{ $spare->location ?? '—' }}
                    </flux:table.cell>
                    <flux:table.cell class="text-right font-mono font-semibold {{ $qtyClass }}">
                        {{ number_format($spare->current_qty, 2) }}
                        @if ($spare->uom)
                            <span class="font-normal text-xs text-zinc-400">{{ $spare->uom->code }}</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm text-zinc-500">
                        {{ number_format((float) $spare->min_qty, 2) }}
                    </flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm text-zinc-500">
                        {{ number_format((float) $spare->max_qty, 2) }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$alertColor" size="sm">{{ $alertLabel }}</flux:badge>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center text-zinc-500 py-12">
                        <flux:icon.archive-box class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No spares found</div>
                        <flux:text class="mt-1">Adjust your search or filters.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Pagination --}}
    @if ($rows->hasPages())
        <div class="mt-4">
            <flux:pagination :paginator="$rows" />
        </div>
    @endif
</div>
