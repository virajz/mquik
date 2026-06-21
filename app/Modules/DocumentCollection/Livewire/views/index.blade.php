<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Document Collection</flux:heading>
            <flux:text class="mt-1">Request, receive and verify customer / insurance documents against a checklist.</flux:text>
        </div>
        @can('document_collection.create')
            <flux:button :href="route('document-collection.create')" wire:navigate variant="primary" icon="plus">New Collection</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search DC no, policy, customer, reg…" icon="magnifying-glass" clearable class="max-w-sm" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All statuses</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="requestTypeFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All sources</flux:select.option>
            @foreach ($requestTypes as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'doc_collection_no'" :direction="$sortDirection" wire:click="sort('doc_collection_no')">DC No.</flux:table.column>
            <flux:table.column>Customer / Vehicle</flux:table.column>
            <flux:table.column class="w-32">Source</flux:table.column>
            <flux:table.column class="w-24">Docs</flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Created</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">{{ $row->doc_collection_no }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium">{{ $row->customer ? trim($row->customer->first_name.' '.($row->customer->last_name ?? '')) : '—' }}</div>
                        <div class="text-xs text-zinc-500">{{ $row->customerVehicle?->registration_no }}{{ $row->insuranceCompany ? ' · '.$row->insuranceCompany->name : '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$row->request_type === 'insurance_claim' ? 'sky' : 'zinc'" size="sm">{{ $requestTypes[$row->request_type] ?? $row->request_type }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500 text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="match ($row->status) {
                            'pending' => 'amber', 'requested' => 'blue', 'received' => 'lime',
                            'rejected' => 'red', 'cancelled' => 'zinc', default => 'zinc',
                        }" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500 text-sm">{{ $row->created_at?->format('d M Y') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('document_collection.update')
                                <flux:button :href="route('document-collection.edit', $row->id)" wire:navigate size="sm" variant="ghost" icon="pencil-square">Open</flux:button>
                            @endcan
                            @can('document_collection.delete')
                                <flux:modal.trigger :name="'dc-delete-'.$row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'dc-delete-'.$row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->doc_collection_no }}?</flux:heading>
                                        <flux:text>This removes the collection and its checklist items. Cannot be undone.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('dc-delete-{{ $row->id }}').close()">Delete</flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500 py-12">
                        <flux:icon.document-arrow-up class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No document collections yet</div>
                        <flux:text class="mt-1">Start one to request and verify customer / insurance documents.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())
        <div class="mt-4"><flux:pagination :paginator="$rows" /></div>
    @endif
</div>
