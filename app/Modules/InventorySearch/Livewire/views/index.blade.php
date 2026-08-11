@use(App\Modules\Inventory\Services\StockLedger)
<div>
    <div class="mb-6">
        <flux:heading size="xl" level="1">Inventory Search</flux:heading>
        <flux:text class="mt-1">Check part availability and vehicle compatibility — filter by vehicle, type, group, brand, rack or vendor.</flux:text>
    </div>

    {{-- FILTERS --}}
    <div class="mb-4 space-y-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by part name, code or HSN…" icon="magnifying-glass" clearable class="max-w-md" />

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            <flux:select wire:model.live="vehicleBrandFilter" variant="listbox" searchable>
                <flux:select.option value="all">All vehicle brands</flux:select.option>
                @foreach ($this->vehicleBrands as $b)
                    <flux:select.option :value="(string) $b->id">{{ $b->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="modelFilter" variant="listbox" searchable :disabled="$vehicleBrandFilter === 'all'" :filter="false">
            <x-slot name="search">
                <flux:select.search wire:model.live.debounce.250ms="modelSearch" placeholder="Type a brand or model…" />
            </x-slot>
                <flux:select.option value="all">All models</flux:select.option>
                @foreach ($this->models as $m)
                    <flux:select.option :value="(string) $m->id">{{ $m->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="variantFilter" variant="listbox" searchable :disabled="$modelFilter === 'all'" :filter="false">
            <x-slot name="search">
                <flux:select.search wire:model.live.debounce.250ms="variantSearch" placeholder="Type a brand, model or variant…" />
            </x-slot>
                <flux:select.option value="all">All variants</flux:select.option>
                @foreach ($this->variants as $v)
                    <flux:select.option :value="(string) $v->id">{{ $v->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="partTypeFilter" variant="listbox">
                <flux:select.option value="all">All part types</flux:select.option>
                @foreach ($this->partTypes as $pt)
                    <flux:select.option :value="(string) $pt->id">{{ $pt->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="groupFilter" variant="listbox" searchable>
                <flux:select.option value="all">All groups</flux:select.option>
                @foreach ($this->inventoryGroups as $g)
                    <flux:select.option :value="(string) $g->id">{{ $g->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="subGroupFilter" variant="listbox" searchable :disabled="$groupFilter === 'all'" :filter="false">
            <x-slot name="search">
                <flux:select.search wire:model.live.debounce.250ms="subGroupSearch" placeholder="Type a group name…" />
            </x-slot>
                <flux:select.option value="all">All sub-groups</flux:select.option>
                @foreach ($this->subGroups as $g)
                    <flux:select.option :value="(string) $g->id">{{ $g->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="partsBrandFilter" variant="listbox" searchable :filter="false">
            <x-slot name="search">
                <flux:select.search wire:model.live.debounce.250ms="partsBrandSearch" placeholder="Type a brand name…" />
            </x-slot>
                <flux:select.option value="all">All parts brands</flux:select.option>
                @foreach ($this->partsBrands as $b)
                    <flux:select.option :value="(string) $b->id">{{ $b->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="uomFilter" variant="listbox" searchable>
                <flux:select.option value="all">All UOM</flux:select.option>
                @foreach ($this->uoms as $u)
                    <flux:select.option :value="(string) $u->id">{{ $u->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="rackFilter" variant="listbox" searchable>
                <flux:select.option value="all">All racks</flux:select.option>
                @foreach ($this->racks as $r)
                    <flux:select.option :value="(string) $r->id">{{ $r->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="vendorFilter" variant="listbox" searchable :filter="false">
            <x-slot name="search">
                <flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Type a vendor name or code…" />
            </x-slot>
                <flux:select.option value="all">All vendors</flux:select.option>
                @foreach ($this->vendors as $v)
                    <flux:select.option :value="(string) $v->id">{{ $v->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="flex items-center gap-4">
            <flux:checkbox wire:model.live="inStockOnly" label="In stock only" />
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear filters</flux:button>
        </div>
    </div>

    {{-- RESULTS --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Part</flux:table.column>
            <flux:table.column class="w-32">Brand</flux:table.column>
            <flux:table.column class="w-28">Type</flux:table.column>
            <flux:table.column class="w-40">Group</flux:table.column>
            <flux:table.column class="w-20">Rack</flux:table.column>
            <flux:table.column class="w-20 text-center">Fits</flux:table.column>
            <flux:table.column class="w-28 text-right">In Stock</flux:table.column>
            <flux:table.column class="w-28" align="end">Alternatives</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                @php
                    $qty = $qtyMap[$row->id] ?? 0.0;
                    $alert = StockLedger::alertStatus($qty, (float) $row->min_qty, (float) $row->max_qty);
                    $alertColor = match ($alert) {
                        'ok' => 'lime', 'below_min' => 'amber', 'above_max' => 'sky', default => 'red',
                    };
                @endphp
                <flux:table.row :key="$row->id">
                    <flux:table.cell>
                        <div class="font-medium text-sm">{{ $row->name }}</div>
                        <div class="text-xs text-zinc-500 font-mono">{{ $row->spare_code ?? '—' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->brand?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($row->partType)
                            <flux:badge size="sm" color="zinc">{{ $row->partType->name }}</flux:badge>
                        @else — @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $row->inventoryGroup?->name ?? '—' }}</div>
                        @if ($row->inventorySubGroup)
                            <div class="text-xs text-zinc-500">{{ $row->inventorySubGroup->name }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-sm font-mono">{{ $row->rack?->name ?? $row->location ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-center text-sm">{{ $row->vehicle_variants_count }}</flux:table.cell>
                    <flux:table.cell class="text-right">
                        <flux:badge :color="$alertColor" size="sm">
                            {{ rtrim(rtrim(number_format($qty, 2), '0'), '.') }} {{ $row->uom?->code ?? '' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end">
                            <flux:modal.trigger name="inv-alternatives">
                                <flux:button size="sm" variant="ghost" icon="arrows-right-left" wire:click="showAlternatives({{ $row->id }})">View</flux:button>
                            </flux:modal.trigger>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center text-zinc-500 py-12">
                        <flux:icon.magnifying-glass class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No parts match these filters</div>
                        <flux:text class="mt-1">Adjust the filters or clear them to see the full catalogue.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif

    {{-- ALTERNATIVES DRAWER --}}
    <flux:modal name="inv-alternatives" variant="flyout" class="w-full max-w-lg">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Alternative Parts</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Same sub-group, fitting at least one shared vehicle.</flux:text>
            </div>
            <div class="space-y-2">
                @forelse ($this->alternativeRows as $alt)
                    @php($altColor = match ($alt['alert']) { 'ok' => 'lime', 'below_min' => 'amber', 'above_max' => 'sky', default => 'red' })
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 dark:border-zinc-800 p-3">
                        <div class="min-w-0">
                            <div class="font-medium text-sm truncate">{{ $alt['name'] }}</div>
                            <div class="text-xs text-zinc-500">
                                <span class="font-mono">{{ $alt['spare_code'] ?? '—' }}</span>
                                @if ($alt['brand']) · {{ $alt['brand'] }} @endif
                                @if ($alt['part_type']) · {{ $alt['part_type'] }} @endif
                                @if ($alt['rack']) · Rack {{ $alt['rack'] }} @endif
                            </div>
                        </div>
                        <flux:badge :color="$altColor" size="sm">{{ rtrim(rtrim(number_format($alt['qty'], 2), '0'), '.') }}</flux:badge>
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-8 text-center text-sm text-zinc-500">
                        No alternatives found for this part.
                    </div>
                @endforelse
            </div>
            <div class="flex justify-end">
                <flux:modal.close><flux:button variant="ghost">Close</flux:button></flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
