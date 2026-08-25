<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Estimate Templates</flux:heading>
            <flux:text class="mt-1">Group-wise default spare/labour sets to pre-fill a sales estimate.</flux:text>
        </div>
        @can('estimate_template_master.create')
            <flux:button variant="primary" icon="plus" :href="route('estimate-template-master.create')" wire:navigate>New Template</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or code…" icon="magnifying-glass" clearable class="max-w-sm" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="inactive">Inactive</flux:select.option>
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column class="w-32">Code</flux:table.column>
            <flux:table.column class="w-48">Group</flux:table.column>
            <flux:table.column class="w-24 text-center">Lines</flux:table.column>
            <flux:table.column class="w-24">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell>
                        <div class="font-medium">{{ $row->name }}</div>
                        <div class="mt-0.5 flex items-center gap-2 text-xs text-zinc-500">
                            @if ($row->category)
                                <flux:badge color="sky" size="sm">{{ \App\Modules\EstimateTemplateMaster\Models\EstimateTemplateMaster::categories()[$row->category] ?? $row->category }}</flux:badge>
                            @endif
                            @if ($row->effective_date)
                                <span>w.e.f. {{ $row->effective_date->format('d/m/Y') }}</span>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="font-mono text-xs text-zinc-500">{{ $row->code ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->inventoryGroup?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-center font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($row->is_active)
                            <flux:badge color="lime" size="sm">Active</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('estimate_template_master.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('estimate-template-master.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('estimate_template_master.delete')
                                <flux:modal.trigger :name="'et-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'et-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                        <flux:text>Cannot be undone. Cascades to its template lines.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('et-delete-{{ $row->id }}').close()">Delete</flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500 py-12">
                        <flux:icon.document-duplicate class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No templates yet</div>
                        <flux:text class="mt-1">Create a template like "PMS Basic" or "Accident Bodyshop".</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
