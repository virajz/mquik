<div>
    {{-- Page heading + primary action + actions menu --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Insurance Companies</flux:heading>
            <flux:text class="mt-1">Insurers this workshop deals with for claims, surveys, and policy renewals.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            @can('insurance_company_master.create')
                <flux:button variant="primary" icon="plus" wire:click="openCreate">
                New Insurance Company
            </flux:button>
            @endcan
            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />

                <flux:menu>
                    @can('insurance_company_master.import')
                    <flux:menu.item icon="arrow-up-tray"
                        wire:click="$dispatch('start-import', { module: 'InsuranceCompanyMaster' })">
                        Import…
                    </flux:menu.item>
                    @endcan
                    @can('insurance_company_master.export')
                    <flux:menu.item icon="arrow-down-tray"
                        wire:click="$dispatch('start-export', { module: 'InsuranceCompanyMaster' })">
                        Export
                    </flux:menu.item>
                    @endcan
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    {{-- Engines — listen for 'start-export' / 'start-import' globally, only act on matching module --}}
    <livewire:import-export.export-button :module="'InsuranceCompanyMaster'" wire:key="export-insurance-company-master" />
    <livewire:import-export.import-wizard :module="'InsuranceCompanyMaster'" wire:key="import-insurance-company-master" />

    {{-- Search bar --}}
    <div class="mb-4 flex items-center gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name, short name, or GSTIN..."
            icon="magnifying-glass"
            class="max-w-md"
        />
        @if ($search)
            <flux:button variant="ghost" size="sm" wire:click="$set('search', '')">
                Clear
            </flux:button>
        @endif
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
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'gstin'" :direction="$sortDirection" wire:click="sort('gstin')">
                GSTIN
            </flux:table.column>
            <flux:table.column>Contact</flux:table.column>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')">
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
                            @if ($row->short_name)
                                <div class="text-xs text-zinc-500 mt-0.5">{{ $row->short_name }}</div>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="font-mono text-xs text-zinc-500">
                            {{ $row->gstin ?? '—' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="text-sm">{{ $row->contact_person ?? '—' }}</div>
                            @if ($row->phone)
                                <div class="text-xs text-zinc-500 mt-0.5">{{ $row->phone }}</div>
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
                                @can('insurance_company_master.update')
                                    <flux:button size="sm" variant="ghost" icon="pencil-square"
                                    wire:click="openEdit({{ $row->id }})">Edit</flux:button>
                                @endcan
                                @can('insurance_company_master.delete')
                                    <flux:modal.trigger :name="'insurance-company-master-delete-' . $row->id">
                                        <flux:button size="sm" variant="ghost" icon="trash" />
                                    </flux:modal.trigger>
                                    <flux:modal :name="'insurance-company-master-delete-' . $row->id">
                                        <div class="space-y-4">
                                            <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                            <flux:text>This cannot be undone. Existing claims and policies linked to this insurer will keep a stale reference.</flux:text>
                                            <div class="flex gap-2 justify-end">
                                                <flux:modal.close>
                                                    <flux:button variant="ghost">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:button variant="danger"
                                                    wire:click="delete({{ $row->id }})"
                                                    x-on:click="$flux.modal('insurance-company-master-delete-{{ $row->id }}').close()">
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
                        <flux:table.cell colspan="6" class="text-center text-zinc-500 py-12">
                            <flux:icon.shield-check class="mx-auto mb-3 size-8 text-zinc-400" />
                            <div class="font-medium">No insurance companies yet</div>
                            <flux:text class="mt-1">Add insurers like New India Assurance, ICICI Lombard, etc.</flux:text>
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

    <livewire:insurance-company-master.form />
</div>
