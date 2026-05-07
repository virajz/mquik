<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Checklist Templates</flux:heading>
            <flux:text class="mt-1">Reusable checklists with inline items. Used by document collection, pre-delivery, safety, etc.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" wire:click="openCreate">New Template</flux:button>

            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />
                <flux:menu>
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'ChecklistTemplateMaster' })">Import…</flux:menu.item>
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'ChecklistTemplateMaster' })">Export</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <livewire:import-export.export-button :module="'ChecklistTemplateMaster'" wire:key="export-checklist-templates" />
    <livewire:import-export.import-wizard :module="'ChecklistTemplateMaster'" wire:key="import-checklist-templates" />

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name or code..."
            icon="magnifying-glass"
            clearable
            class="max-w-sm"
        />

        <flux:select wire:model.live="groupFilter" variant="listbox" searchable class="max-w-56">
            <flux:select.option value="all">All groups</flux:select.option>
            @foreach ($groupOptions as $g)
                <flux:select.option :value="(string) $g->id">{{ $g->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="appliesToFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All applies to</flux:select.option>
            @foreach ($appliesToOptions as $opt)
                <flux:select.option :value="$opt">{{ ucfirst(str_replace('_', ' ', $opt)) }}</flux:select.option>
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
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'code'" :direction="$sortDirection" wire:click="sort('code')">Code</flux:table.column>
            <flux:table.column class="w-44" sortable :sorted="$sortBy === 'checklist_group_id'" :direction="$sortDirection" wire:click="sort('checklist_group_id')">Group</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'applies_to'" :direction="$sortDirection" wire:click="sort('applies_to')">Applies To</flux:table.column>
            <flux:table.column class="w-24" align="end">Items</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">#{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}</flux:table.cell>
                    <flux:table.cell class="font-medium">{{ $row->name }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs text-zinc-500">{{ $row->code ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($row->group)
                            <flux:badge color="zinc" size="sm">{{ $row->group->name }}</flux:badge>
                        @else
                            <span class="text-zinc-400 text-xs">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @php
                            $colorMap = [
                                'job_card' => 'zinc',
                                'pickup' => 'blue',
                                'delivery' => 'lime',
                                'claim' => 'amber',
                                'generic' => 'purple',
                            ];
                            $color = $colorMap[$row->applies_to] ?? 'zinc';
                        @endphp
                        <flux:badge :color="$color" size="sm">{{ ucfirst(str_replace('_', ' ', $row->applies_to)) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-xs">{{ count($row->items ?? []) }} items</flux:table.cell>
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
                            <flux:modal.trigger :name="'checklist-template-master-delete-' . $row->id">
                                <flux:button size="sm" variant="ghost" icon="trash" />
                            </flux:modal.trigger>
                            <flux:modal :name="'checklist-template-master-delete-' . $row->id">
                                <div class="space-y-4">
                                    <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                    <flux:text>Cannot be undone. The items inside this template will be lost.</flux:text>
                                    <div class="flex gap-2 justify-end">
                                        <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                        <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('checklist-template-master-delete-{{ $row->id }}').close()">Delete</flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center text-zinc-500 py-12">
                        <flux:icon.clipboard-document-check class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No templates yet</div>
                        <flux:text class="mt-1">Add templates like Document Collection (RC, DL, Aadhar, PAN), Pre-Delivery (Washed, Floor Mats, Fuel Topped Up).</flux:text>
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

    <livewire:checklist-template-master.form />
</div>
