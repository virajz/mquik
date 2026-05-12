<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Service Types</flux:heading>
            <flux:text class="mt-1">How the workshop categorises a job — drives advisor routing, packages, and reports.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" wire:click="openCreate">
                New Service Type
            </flux:button>

            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />

                <flux:menu>
                    @can('service_type_master.import')
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'ServiceTypeMaster' })">
                        Import…
                    </flux:menu.item>
                    @endcan
                    @can('service_type_master.export')
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'ServiceTypeMaster' })">
                        Export
                    </flux:menu.item>
                    @endcan
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <livewire:import-export.export-button :module="'ServiceTypeMaster'" wire:key="export-service-types" />
    <livewire:import-export.import-wizard :module="'ServiceTypeMaster'" wire:key="import-service-types" />

    <div class="mb-4 flex items-center gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name or code..."
            icon="magnifying-glass"
            clearable
            class="max-w-sm"
        />

        <flux:select wire:model.live="departmentFilter" variant="listbox" searchable class="max-w-44">
            <flux:select.option value="all">All departments</flux:select.option>
            @foreach ($departments as $d)
                <flux:select.option :value="(string) $d->id">{{ $d->name }}</flux:select.option>
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
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">
                ID
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">
                Service Type
            </flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'code'" :direction="$sortDirection" wire:click="sort('code')">
                Code
            </flux:table.column>
            <flux:table.column class="w-40" sortable :sorted="$sortBy === 'workshop_department_id'" :direction="$sortDirection" wire:click="sort('workshop_department_id')">
                Department
            </flux:table.column>
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

                    <flux:table.cell>
                        @if ($row->workshopDepartment)
                            <flux:badge color="zinc" size="sm">{{ $row->workshopDepartment->name }}</flux:badge>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
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
                            <flux:button size="sm" variant="ghost" icon="pencil-square"
                                wire:click="openEdit({{ $row->id }})">Edit</flux:button>

                            <flux:modal.trigger :name="'service-type-master-delete-' . $row->id">
                                <flux:button size="sm" variant="ghost" icon="trash" />
                            </flux:modal.trigger>

                            <flux:modal :name="'service-type-master-delete-' . $row->id">
                                <div class="space-y-4">
                                    <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                    <flux:text>Cannot be undone. If any job descriptions or job cards reference this service type the delete will fail.</flux:text>
                                    <div class="flex gap-2 justify-end">
                                        <flux:modal.close>
                                            <flux:button variant="ghost">Cancel</flux:button>
                                        </flux:modal.close>
                                        <flux:button variant="danger"
                                            wire:click="delete({{ $row->id }})"
                                            x-on:click="$flux.modal('service-type-master-delete-{{ $row->id }}').close()">
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
                    <flux:table.cell colspan="6" class="text-center text-zinc-500 py-12">
                        <flux:icon.wrench-screwdriver class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No service types yet</div>
                        <flux:text class="mt-1">Add types like Periodic Maintenance, General Repair, Bodyshop, Tyre.</flux:text>
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

    <livewire:service-type-master.form />
</div>
