<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Job Descriptions</flux:heading>
            <flux:text class="mt-1">Catalog of jobs the workshop performs — used by job cards and estimates.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            @can('job_description_master.create')
                <flux:button variant="primary" icon="plus" wire:click="openCreate">New Job Description</flux:button>
            @endcan
            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />
                <flux:menu>
                    @can('job_description_master.import')
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'JobDescriptionMaster' })">Import…</flux:menu.item>
                    @endcan
                    @can('job_description_master.export')
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'JobDescriptionMaster' })">Export</flux:menu.item>
                    @endcan
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <livewire:import-export.export-button :module="'JobDescriptionMaster'" wire:key="export-job-descriptions" />
    <livewire:import-export.import-wizard :module="'JobDescriptionMaster'" wire:key="import-job-descriptions" />

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name or code..."
            icon="magnifying-glass"
            clearable
            class="max-w-sm"
        />

        <flux:select wire:model.live="categoryFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All categories</flux:select.option>
            @foreach ($categories as $c)
                <flux:select.option :value="$c">{{ ucfirst($c) }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="serviceTypeFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All service types</flux:select.option>
            @foreach ($serviceTypes as $st)
                <flux:select.option :value="(string) $st->id">{{ $st->name }}</flux:select.option>
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
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Job Description</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'code'" :direction="$sortDirection" wire:click="sort('code')">Code</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'category'" :direction="$sortDirection" wire:click="sort('category')">Category</flux:table.column>
            <flux:table.column class="w-44">Service Type</flux:table.column>
            <flux:table.column class="w-24" align="end" sortable :sorted="$sortBy === 'standard_hours'" :direction="$sortDirection" wire:click="sort('standard_hours')">Hours</flux:table.column>
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
                        @if ($row->category === 'frequent')
                            <flux:badge color="amber" size="sm">Frequent</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">General</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500 text-sm">{{ $row->serviceType?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-xs">
                        {{ $row->standard_hours !== null ? number_format($row->standard_hours, 2) : '—' }}
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
                            @can('job_description_master.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $row->id }})">Edit</flux:button>
                            @endcan
                            @can('job_description_master.delete')
                                <flux:modal.trigger :name="'job-description-master-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'job-description-master-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                        <flux:text>Cannot be undone. If this description is referenced by job cards or estimates the delete will fail.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('job-description-master-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.list-bullet class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No job descriptions yet</div>
                        <flux:text class="mt-1">Add tasks like Engine Oil Change, Brake Pad Replacement, AC Gas Refill.</flux:text>
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

    <livewire:job-description-master.form />
</div>
