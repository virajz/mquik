<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Labour</flux:heading>
            <flux:text class="mt-1">Labour catalog — services, HSN/SAC, segment, rate, and OSL flag for outside-labour items.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('labour_master.create')
                <flux:button variant="primary" icon="plus" wire:click="openCreate">New Labour</flux:button>
            @endcan
            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />
                <flux:menu>
                    @can('labour_master.import')
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'LabourMaster' })">Import…</flux:menu.item>
                    @endcan
                    @can('labour_master.export')
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'LabourMaster' })">Export</flux:menu.item>
                    @endcan
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name, code, or HSN/SAC..." icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="segmentFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All segments</flux:select.option>
            @foreach ($this->segments as $s)
                <flux:select.option :value="(string) $s->id">{{ $s->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="oslFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">In-house & OSL</flux:select.option>
            <flux:select.option value="in-house">In-house</flux:select.option>
            <flux:select.option value="osl">OSL only</flux:select.option>
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
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'labour_code'" :direction="$sortDirection" wire:click="sort('labour_code')">Code</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column class="w-36">Segment</flux:table.column>
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'rate_before_tax'" :direction="$sortDirection" wire:click="sort('rate_before_tax')">Rate</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">#{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $row->labour_code ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium">{{ $row->name }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 flex items-center gap-2">
                            @if ($row->is_osl)
                                <flux:badge color="amber" size="sm">OSL</flux:badge>
                            @endif
                            @if ($row->hsn)
                                <span class="font-mono">SAC {{ $row->hsn->code }}</span>
                            @endif
                            @if ($row->workshopDepartment)
                                <span>· {{ $row->workshopDepartment->name }}</span>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500 text-sm">{{ $row->vehicleSegment?->name ?? '—' }}</flux:table.cell>
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
                            @can('labour_master.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $row->id }})">Edit</flux:button>
                            @endcan
                            @can('labour_master.delete')
                                <flux:modal.trigger :name="'labour-master-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'labour-master-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                        <flux:text>Cannot be undone. If this labour appears in any estimate or invoice the delete will fail.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('labour-master-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.wrench-screwdriver class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No labour entries yet</div>
                        <flux:text class="mt-1">Add the labours you bill for — services, repairs, paint jobs.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif

    <livewire:labour-master.form />
    <livewire:import-export.export-button :module="'LabourMaster'" wire:key="export-labour-master" />
    <livewire:import-export.import-wizard :module="'LabourMaster'" wire:key="import-labour-master" />
</div>
