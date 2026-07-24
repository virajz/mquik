<div>
    {{-- Page heading + primary action + actions menu --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">HSN / SAC Codes</flux:heading>
            <flux:text class="mt-1">GST classification codes — HSN for goods, SAC for services, with the default rate.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            @can('hsn_master.create')
                <flux:button variant="primary" icon="plus" wire:click="openCreate">
                New Code
            </flux:button>
            @endcan
            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />

                <flux:menu>
                    @can('hsn_master.import')
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'HsnMaster' })">
                        Import…
                    </flux:menu.item>
                    @endcan
                    @can('hsn_master.export')
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'HsnMaster' })">
                        Export
                    </flux:menu.item>
                    @endcan
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    {{-- Engines — listen for 'start-export' / 'start-import' globally, only act on matching module --}}
    <livewire:import-export.export-button :module="'HsnMaster'" wire:key="export-hsn-codes" />
    <livewire:import-export.import-wizard :module="'HsnMaster'" wire:key="import-hsn-codes" />

    {{-- Filter bar --}}
    <div class="mb-4 flex items-center gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by code or description..."
            icon="magnifying-glass"
            clearable
            class="max-w-sm"
        />

        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="inactive">Inactive</flux:select.option>
        </flux:select>
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">
                ID
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">
                Name
            </flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'code'" :direction="$sortDirection" wire:click="sort('code')">
                Code
            </flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'kind'" :direction="$sortDirection" wire:click="sort('kind')">
                Kind
            </flux:table.column>
            <flux:table.column class="w-24" align="end" sortable :sorted="$sortBy === 'gst_percent'" :direction="$sortDirection" wire:click="sort('gst_percent')">
                GST %
            </flux:table.column>
            <flux:table.column>Notes</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')">
                Status
            </flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">
                        #{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}
                    </flux:table.cell>

                    <flux:table.cell class="font-medium">{{ $row->name }}</flux:table.cell>

                    <flux:table.cell class="font-mono text-xs text-zinc-500">
                        {{ $row->code ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-500 text-sm">
                        {{ \App\Modules\HsnMaster\Models\HsnMaster::kinds()[$row->kind] ?? $row->kind }}
                    </flux:table.cell>

                    <flux:table.cell class="text-end font-mono text-sm">
                        {{ $row->gst_percent === null ? '—' : rtrim(rtrim(number_format((float) $row->gst_percent, 2), '0'), '.').'%' }}
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-500 max-w-md truncate">
                        {{ $row->notes ?? '' }}
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
                            @can('hsn_master.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square"
                                wire:click="openEdit({{ $row->id }})">Edit</flux:button>
                            @endcan
                            @can('hsn_master.delete')
                                <flux:modal.trigger :name="'hsn-master-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'hsn-master-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                        <flux:text>Cannot be undone. If any appointments are using this HSN / SAC code the delete will fail and you'll see a warning.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close>
                                                <flux:button variant="ghost">Cancel</flux:button>
                                            </flux:modal.close>
                                            <flux:button variant="danger"
                                                wire:click="delete({{ $row->id }})"
                                                x-on:click="$flux.modal('hsn-master-delete-{{ $row->id }}').close()">
                                                Delete
                                            </flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center text-zinc-500 py-12">
                        <flux:icon.exclamation-triangle class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No codes yet</div>
                        <flux:text class="mt-1">Add types like Engine Noise, AC Issue, Brake, Electrical, Body, Tyre.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())
        <div class="mt-4">
            <flux:pagination :paginator="$rows" />
        </div>
    @endif

    <livewire:hsn-master.form />
</div>
