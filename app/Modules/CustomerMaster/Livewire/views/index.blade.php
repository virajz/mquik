<div>
    {{-- Page heading + primary action + actions menu --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Customers</flux:heading>
            <flux:text class="mt-1">Customers who book services, buy parts, or own vehicles serviced here.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" wire:click="openCreate">
                New Customer
            </flux:button>

            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />

                <flux:menu>
                    <flux:menu.item icon="arrow-up-tray"
                        wire:click="$dispatch('start-import', { module: 'CustomerMaster' })">
                        Import…
                    </flux:menu.item>
                    <flux:menu.item icon="arrow-down-tray"
                        wire:click="$dispatch('start-export', { module: 'CustomerMaster' })">
                        Export
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="mb-4 flex items-center gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name, phone, email, Aadhar, or PAN..."
            icon="magnifying-glass"
            clearable
            class="max-w-md"
        />

        <flux:select wire:model.live="typeFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All types</flux:select.option>
            <flux:select.option value="walking">Walking</flux:select.option>
            <flux:select.option value="loyal">Loyal</flux:select.option>
            <flux:select.option value="corporate">Corporate</flux:select.option>
        </flux:select>

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
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'customer_type'" :direction="$sortDirection" wire:click="sort('customer_type')">
                Type
            </flux:table.column>
            <flux:table.column>Email</flux:table.column>
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

                    <flux:table.cell>
                        <div class="font-medium">{{ $row->name }}</div>
                        @if ($row->phone)
                            <div class="text-xs text-zinc-500 mt-0.5 font-mono">+91 {{ $row->phone }}</div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @php
                            $color = match ($row->customer_type) {
                                'loyal' => 'amber',
                                'corporate' => 'blue',
                                default => 'zinc',
                            };
                        @endphp
                        <flux:badge :color="$color" size="sm">{{ ucfirst($row->customer_type) }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-500 text-sm">
                        {{ $row->email ?? '—' }}
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

                            <flux:modal.trigger :name="'customer-master-delete-' . $row->id">
                                <flux:button size="sm" variant="ghost" icon="trash" />
                            </flux:modal.trigger>

                            <flux:modal :name="'customer-master-delete-' . $row->id">
                                <div class="space-y-4">
                                    <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                    <flux:text>Cannot be undone. If this customer has related records (job cards, invoices) the delete will fail and you'll see a warning.</flux:text>
                                    <div class="flex gap-2 justify-end">
                                        <flux:modal.close>
                                            <flux:button variant="ghost">Cancel</flux:button>
                                        </flux:modal.close>
                                        <flux:button variant="danger"
                                            wire:click="delete({{ $row->id }})"
                                            x-on:click="$flux.modal('customer-master-delete-{{ $row->id }}').close()">
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
                        <flux:icon.user-circle class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No customers yet</div>
                        <flux:text class="mt-1">Add a customer to start booking appointments and creating job cards.</flux:text>
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

    {{-- Engines --}}
    <livewire:customer-master.form />
    <livewire:import-export.export-button :module="'CustomerMaster'" wire:key="export-customer-master" />
    <livewire:import-export.import-wizard :module="'CustomerMaster'" wire:key="import-customer-master" />
</div>
