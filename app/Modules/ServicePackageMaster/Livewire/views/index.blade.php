<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Service Packages</flux:heading>
            <flux:text class="mt-1">Combo and AMC bundles — services included, validity in months/km, package price.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('service_package_master.create')
                <flux:button variant="primary" icon="plus" wire:click="openCreate">New Package</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or code..." icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="kindFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">Combo + AMC</flux:select.option>
            <flux:select.option value="combo">Combo only</flux:select.option>
            <flux:select.option value="amc">AMC only</flux:select.option>
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
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'code'" :direction="$sortDirection" wire:click="sort('code')">Code</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column class="w-32">Validity</flux:table.column>
            <flux:table.column class="w-24" align="end">Services</flux:table.column>
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'total_price'" :direction="$sortDirection" wire:click="sort('total_price')">Price</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">#{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $row->code ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium">{{ $row->name }}</div>
                        @if ($row->is_amc)
                            <div class="mt-0.5"><flux:badge color="amber" size="sm">AMC</flux:badge></div>
                        @else
                            <div class="mt-0.5"><flux:badge color="zinc" size="sm">Combo</flux:badge></div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">
                        @if ($row->validity_months)
                            <div>{{ $row->validity_months }} mo</div>
                        @endif
                        @if ($row->validity_km)
                            <div class="text-xs">or {{ number_format($row->validity_km) }} km</div>
                        @endif
                        @if (! $row->validity_months && ! $row->validity_km)
                            —
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm">{{ $row->services_count }}</flux:table.cell>
                    <flux:table.cell class="text-sm text-right font-mono">
                        ₹ {{ number_format((float) $row->total_price, 2) }}
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
                            @can('service_package_master.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $row->id }})">Edit</flux:button>
                            @endcan
                            @can('service_package_master.delete')
                                <flux:modal.trigger :name="'service-package-master-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'service-package-master-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                        <flux:text>Cannot be undone. Customers currently on this package will lose package linkage.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('service-package-master-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.gift class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No service packages yet</div>
                        <flux:text class="mt-1">Bundle services into combos or AMCs to drive repeat business.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif

    <livewire:service-package-master.form />
</div>
