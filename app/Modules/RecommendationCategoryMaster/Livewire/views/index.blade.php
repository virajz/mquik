<div>
    {{-- Page heading + primary action + actions menu --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Recommendation Categories</flux:heading>
            <flux:text class="mt-1">Categories and their sub categories, used to file recommendation wording.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" wire:click="openCreate">
                New category
            </flux:button>

            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />

                <flux:menu>
                    <flux:menu.item icon="arrow-up-tray"
                        wire:click="$dispatch('start-import', { module: 'RecommendationCategoryMaster' })">
                        Import…
                    </flux:menu.item>
                    <flux:menu.item icon="arrow-down-tray"
                        wire:click="$dispatch('start-export', { module: 'RecommendationCategoryMaster' })">
                        Export
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    {{-- Engines — listen for 'start-export' / 'start-import' globally, only act on matching module --}}
    <livewire:import-export.export-button :module="'RecommendationCategoryMaster'" wire:key="export-recommendation-category-master" />
    <livewire:import-export.import-wizard :module="'RecommendationCategoryMaster'" wire:key="import-recommendation-category-master" />

    {{-- Search bar --}}
    <div class="mb-4 flex items-center gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name or code..."
            icon="magnifying-glass"
            clearable
            class="max-w-sm"
        />
    </div>

    {{-- Table — no border/card wrapper (UI convention) --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">
                ID
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">
                Name
            </flux:table.column>
            <flux:table.column class="w-40">Parent</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'code'" :direction="$sortDirection" wire:click="sort('code')">
                Code
            </flux:table.column>
            <flux:table.column class="w-24 text-center" sortable :sorted="$sortBy === 'sequence_no'" :direction="$sortDirection" wire:click="sort('sequence_no')">
                Order
            </flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')">
                Status
            </flux:table.column>
            <flux:table.column class="w-40" sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">
                Created
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
                    <flux:table.cell class="text-zinc-500 text-sm">
                        {{ $row->parent?->name ?? '— top level —' }}
                    </flux:table.cell>
                    <flux:table.cell class="font-mono text-xs text-zinc-500">{{ $row->code ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-center font-mono text-xs text-zinc-500">{{ $row->sequence_no }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$row->is_active ? 'lime' : 'zinc'" size="sm">
                            {{ $row->is_active ? 'Active' : 'Inactive' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500 text-sm">{{ $row->created_at?->diffForHumans() }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            <flux:button size="sm" variant="ghost" icon="pencil-square"
                                wire:click="openEdit({{ $row->id }})">Edit</flux:button>

                            <flux:modal.trigger :name="'recommendation-category-master-delete-' . $row->id">
                                <flux:button size="sm" variant="ghost" icon="trash" />
                            </flux:modal.trigger>

                            <flux:modal :name="'recommendation-category-master-delete-' . $row->id">
                                <div class="space-y-4">
                                    <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                    <flux:text>This cannot be undone.</flux:text>
                                    <div class="flex gap-2 justify-end">
                                        <flux:modal.close>
                                            <flux:button variant="ghost">Cancel</flux:button>
                                        </flux:modal.close>
                                        <flux:button variant="danger"
                                            wire:click="delete({{ $row->id }})"
                                            x-on:click="$flux.modal('recommendation-category-master-delete-{{ $row->id }}').close()">
                                            Delete
                                        </flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center text-zinc-500 py-12">
                        <flux:icon.inbox class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No records yet</div>
                        <flux:text class="mt-1">Create a category first, then file sub categories under it.</flux:text>
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

    {{-- Form modal (nested Livewire component) --}}
    <livewire:recommendation-category-master.form />
</div>
