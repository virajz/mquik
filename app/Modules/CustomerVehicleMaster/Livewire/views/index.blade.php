<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Customer Vehicles</flux:heading>
            <flux:text class="mt-1">Vehicles owned by customers — used by appointments, job cards, and insurance claims.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" :href="route('customer-vehicle-master.create')" wire:navigate>New Vehicle</flux:button>
            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />
                <flux:menu>
                    @can('customer_vehicle_master.import')
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'CustomerVehicleMaster' })">Import…</flux:menu.item>
                    @endcan
                    @can('customer_vehicle_master.export')
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'CustomerVehicleMaster' })">Export</flux:menu.item>
                    @endcan
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by reg no, VIN, customer name, or phone..." icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="inactive">Inactive</flux:select.option>
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">ID</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'registration_no'" :direction="$sortDirection" wire:click="sort('registration_no')">Reg. No.</flux:table.column>
            <flux:table.column>Vehicle</flux:table.column>
            <flux:table.column>Owner</flux:table.column>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'year_of_manufacture'" :direction="$sortDirection" wire:click="sort('year_of_manufacture')">Year</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">#{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}</flux:table.cell>
                    <flux:table.cell class="font-mono font-medium tracking-wide">{{ $row->registration_no }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium">{{ $row->model?->brand?->name }} {{ $row->model?->name }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 flex items-center gap-2">
                            @if ($row->variant) <span>{{ $row->variant->name }}</span> @endif
                            @if ($row->color)
                                <span class="inline-flex items-center gap-1">
                                    <span class="size-2 rounded-full border border-zinc-300" style="background-color: {{ $row->color->hex_code ?? '#ccc' }}"></span>
                                    {{ $row->color->name }}
                                </span>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="text-sm">{{ $row->customer?->name ?? '—' }}</div>
                        @if ($row->customer?->phone)
                            <div class="text-xs text-zinc-500 mt-0.5 font-mono">+91 {{ $row->customer->phone }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500 text-sm">{{ $row->year_of_manufacture ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($row->is_active)
                            <flux:badge color="lime" size="sm">Active</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('customer-vehicle-master.edit', $row)" wire:navigate>Edit</flux:button>
                            <flux:modal.trigger :name="'customer-vehicle-master-delete-' . $row->id">
                                <flux:button size="sm" variant="ghost" icon="trash" />
                            </flux:modal.trigger>
                            <flux:modal :name="'customer-vehicle-master-delete-' . $row->id">
                                <div class="space-y-4">
                                    <flux:heading size="lg">Delete {{ $row->registration_no }}?</flux:heading>
                                    <flux:text>Cannot be undone. If this vehicle has any job cards or invoices the delete will fail.</flux:text>
                                    <div class="flex gap-2 justify-end">
                                        <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                        <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('customer-vehicle-master-delete-{{ $row->id }}').close()">Delete</flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500 py-12">
                        <flux:icon.truck class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No customer vehicles yet</div>
                        <flux:text class="mt-1">Add a vehicle to start booking appointments and creating job cards.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif

    <livewire:import-export.export-button :module="'CustomerVehicleMaster'" wire:key="export-customer-vehicle-master" />
    <livewire:import-export.import-wizard :module="'CustomerVehicleMaster'" wire:key="import-customer-vehicle-master" />
</div>
