<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Spares</flux:heading>
            <flux:text class="mt-1">Spare parts catalog — brand, HSN, rate, stock thresholds, tyre details and vehicle compatibility.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('spare_master.create')
                <flux:button variant="primary" icon="plus" :href="route('spare-master.create')" wire:navigate>New Spare</flux:button>
            @endcan
            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />
                <flux:menu>
                    @can('spare_master.import')
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'SpareMaster' })">Import…</flux:menu.item>
                    @endcan
                    @can('spare_master.export')
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'SpareMaster' })">Export</flux:menu.item>
                    @endcan
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name, part no., or HSN..." icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="brandFilter" variant="listbox" searchable class="max-w-52" :filter="false">
        <x-slot name="search">
            <flux:select.search wire:model.live.debounce.250ms="brandSearch" placeholder="Type a brand name…" />
        </x-slot>
            <flux:select.option value="all">All brands</flux:select.option>
            @foreach ($this->brands as $b)
                <flux:select.option :value="(string) $b->id">{{ $b->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="categoryFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All categories</flux:select.option>
            <flux:select.option value="general">General</flux:select.option>
            <flux:select.option value="tyre">Tyre</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="inactive">Inactive</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="departmentFilter" variant="listbox" searchable clearable class="max-w-44">
            <flux:select.option value="all">All departments</flux:select.option>
            @foreach ($this->departments as $d)
                <flux:select.option :value="(string) $d->id" wire:key="dept-{{ $d->id }}">{{ $d->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="groupFilter" variant="listbox" searchable clearable class="max-w-44">
            <flux:select.option value="all">All groups</flux:select.option>
            @foreach ($this->inventoryGroups as $g)
                <flux:select.option :value="(string) $g->id" wire:key="grp-{{ $g->id }}">{{ $g->name }}</flux:select.option>
            @endforeach
        </flux:select>

        {{-- Fits-this-vehicle: model narrows the list, variant narrows it further. --}}
        <flux:select wire:model.live="modelFilter" variant="listbox" searchable clearable :filter="false" class="max-w-52">
            <x-slot name="search">
                <flux:select.search wire:model.live.debounce.250ms="modelSearch" placeholder="Type a brand or model…" />
            </x-slot>
            <flux:select.option value="all">Fits any vehicle</flux:select.option>
            @foreach ($this->models as $m)
                <flux:select.option :value="(string) $m->id" wire:key="mdl-{{ $m->id }}">{{ $m->brand?->name }} {{ $m->name }}</flux:select.option>
            @endforeach
        </flux:select>

        @if ($modelFilter !== 'all')
            <flux:select wire:model.live="variantFilter" variant="listbox" searchable clearable :filter="false" class="max-w-44">
                <x-slot name="search">
                    <flux:select.search wire:model.live.debounce.250ms="variantSearch" placeholder="Type a variant…" />
                </x-slot>
                <flux:select.option value="all">All variants</flux:select.option>
                @foreach ($this->variants as $v)
                    <flux:select.option :value="(string) $v->id" wire:key="vrt-{{ $v->id }}">{{ $v->name }}{{ $v->year ? ' · '.$v->year : '' }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        @if ($this->hasActiveFilters())
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">ID</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'spare_code'" :direction="$sortDirection" wire:click="sort('spare_code')">Part No.</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column class="w-40">Brand</flux:table.column>
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'rate_before_tax'" :direction="$sortDirection" wire:click="sort('rate_before_tax')">Rate</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">#{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $row->spare_code ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium">{{ $row->name }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 flex items-center gap-2">
                            @php($typeColor = match ($row->spare_type) {
                                \App\Modules\SpareMaster\Models\SpareMaster::TYPE_TYRE => 'blue',
                                \App\Modules\SpareMaster\Models\SpareMaster::TYPE_COMMON => 'zinc',
                                default => 'purple',
                            })
                            <flux:badge :color="$typeColor" size="sm">
                                {{ \App\Modules\SpareMaster\Models\SpareMaster::spareTypes()[$row->spare_type] ?? $row->spare_type }}
                            </flux:badge>
                            @if ($row->hsn)
                                <span class="font-mono">HSN {{ $row->hsn->code }}</span>
                            @endif
                            @if ($row->uom)
                                <span>· {{ $row->uom->code ?? $row->uom->name }}</span>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500 text-sm">{{ $row->brand?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm text-right font-mono">
                        ₹ {{ number_format((float) $row->rate_before_tax, 2) }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($row->is_active)
                            <flux:badge color="lime" size="sm">Active</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('spare_master.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('spare-master.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('spare_master.delete')
                                <flux:modal.trigger :name="'spare-master-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'spare-master-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                        <flux:text>Cannot be undone. If this spare appears in any purchase or job card the delete will fail.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('spare-master-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.cube class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No spares yet</div>
                        <flux:text class="mt-1">Add the spares you stock — brake pads, filters, tyres, batteries.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif

    <livewire:import-export.export-button :module="'SpareMaster'" wire:key="export-spare-master" />
    <livewire:import-export.import-wizard :module="'SpareMaster'" wire:key="import-spare-master" />
</div>
