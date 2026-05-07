<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Vehicle Models</flux:heading>
            <flux:text class="mt-1">Models within each brand (e.g. Maruti Swift, Hyundai Creta).</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" wire:click="openCreate">New Model</flux:button>
            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />
                <flux:menu>
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'VehicleModelMaster' })">Import…</flux:menu.item>
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'VehicleModelMaster' })">Export</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by model name..." icon="magnifying-glass" clearable class="max-w-sm" />
        <flux:select wire:model.live="brandFilter" variant="listbox" class="max-w-48">
            <flux:select.option value="all">All brands</flux:select.option>
            @foreach ($this->brands as $b)
                <flux:select.option :value="(string) $b->id">{{ $b->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="inactive">Inactive</flux:select.option>
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">ID</flux:table.column>
            <flux:table.column>Brand</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Model</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'vehicle_segment_id'" :direction="$sortDirection" wire:click="sort('vehicle_segment_id')">Segment</flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'fuel_type'" :direction="$sortDirection" wire:click="sort('fuel_type')">Fuel</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">#{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}</flux:table.cell>
                    <flux:table.cell class="text-zinc-500">{{ $row->brand?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="font-medium">{{ $row->name }}</flux:table.cell>
                    <flux:table.cell class="text-zinc-500 text-sm">{{ $row->vehicleSegment?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-zinc-500 text-sm">{{ $row->fuel_type ? ucfirst($row->fuel_type) : '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($row->is_active)
                            <flux:badge color="lime" size="sm">Active</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $row->id }})">Edit</flux:button>
                            <flux:modal.trigger :name="'vehicle-model-master-delete-' . $row->id">
                                <flux:button size="sm" variant="ghost" icon="trash" />
                            </flux:modal.trigger>
                            <flux:modal :name="'vehicle-model-master-delete-' . $row->id">
                                <div class="space-y-4">
                                    <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                    <flux:text>Cannot be undone. If any customer vehicles use this model the delete will fail.</flux:text>
                                    <div class="flex gap-2 justify-end">
                                        <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                        <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('vehicle-model-master-delete-{{ $row->id }}').close()">Delete</flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500 py-12">
                        <flux:icon.cube class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No vehicle models yet</div>
                        <flux:text class="mt-1">Add models like Swift, Creta, Nexon under their brands.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif

    <livewire:vehicle-model-master.form />
    <livewire:import-export.export-button :module="'VehicleModelMaster'" wire:key="export-vehicle-model-master" />
    <livewire:import-export.import-wizard :module="'VehicleModelMaster'" wire:key="import-vehicle-model-master" />
</div>
