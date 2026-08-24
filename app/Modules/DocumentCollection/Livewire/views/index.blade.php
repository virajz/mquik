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

    <div class="mb-4 flex items-center gap-3">
        <div class="flex-1 min-w-0">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="DC no, job card no, policy no, customer, reg no, brand/model…" icon="magnifying-glass" clearable class="w-full" />
        </div>

        <div class="w-44 shrink-0">
            <flux:select wire:model.live="statusFilter" variant="listbox" class="w-full">
                <flux:select.option value="open">Open (outstanding)</flux:select.option>
                <flux:select.option value="all">All statuses</flux:select.option>
                @foreach ($statuses as $key => $label)
                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @php($filtersOn = $requestTypeFilter !== 'all' || $deptFilter !== 'all' || $createdByFilter !== 'all' || $collectedByFilter !== 'all' || $dateFrom || $dateTo)
        <flux:dropdown class="shrink-0">
            <flux:button icon="funnel" variant="{{ $filtersOn ? 'primary' : 'outline' }}">Filters</flux:button>
            <flux:popover class="w-80 space-y-4">
                <flux:select wire:model.live="requestTypeFilter" variant="listbox" label="Request Source">
                    <flux:select.option value="all">All sources</flux:select.option>
                    @foreach ($requestTypes as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="deptFilter" variant="listbox" searchable label="Department">
                    <flux:select.option value="all">All departments</flux:select.option>
                    @foreach ($this->departments as $d)
                        <flux:select.option :value="(string) $d->id" wire:key="fdept-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="createdByFilter" variant="listbox" searchable label="Created By">
                    <flux:select.option value="all">Anyone</flux:select.option>
                    @foreach ($this->employees as $e)
                        <flux:select.option :value="(string) $e->id" wire:key="fcb-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="collectedByFilter" variant="listbox" searchable label="Collected By">
                    <flux:select.option value="all">Anyone</flux:select.option>
                    @foreach ($this->employees as $e)
                        <flux:select.option :value="(string) $e->id" wire:key="fcol-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:separator variant="subtle" />

                <flux:field>
                    <flux:label>Requested date</flux:label>
                    <div class="grid grid-cols-2 gap-2">
                        <flux:date-picker wire:model.live="dateFrom" placeholder="From" with-today selectable-header fixed-weeks type="input" clearable />
                        <flux:date-picker wire:model.live="dateTo" placeholder="To" with-today selectable-header fixed-weeks type="input" clearable />
                    </div>
                </flux:field>
            </flux:popover>
        </flux:dropdown>

        @if ($search || $statusFilter !== 'open' || $filtersOn)
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters" class="shrink-0">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'doc_collection_no'" :direction="$sortDirection" wire:click="sort('doc_collection_no')">DC No.</flux:table.column>
            <flux:table.column class="w-40" sortable :sorted="$sortBy === 'requested_at'" :direction="$sortDirection" wire:click="sort('requested_at')">Requested</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'received_at'" :direction="$sortDirection" wire:click="sort('received_at')">Received</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'vehicle'" :direction="$sortDirection" wire:click="sort('vehicle')">Customer / Vehicle</flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'job_card'" :direction="$sortDirection" wire:click="sort('job_card')">Job No.</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'department'" :direction="$sortDirection" wire:click="sort('department')">Dept</flux:table.column>
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'created_by'" :direction="$sortDirection" wire:click="sort('created_by')">Created By</flux:table.column>
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'collected_by'" :direction="$sortDirection" wire:click="sort('collected_by')">Collected By</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'request_type'" :direction="$sortDirection" wire:click="sort('request_type')">Source</flux:table.column>
            <flux:table.column class="w-24">Docs</flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">{{ $row->doc_collection_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div class="font-medium">{{ $row->requested_at?->format('d M Y') ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->requested_at?->format('h:i A') }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $row->received_at?->format('d M Y') ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium">{{ $row->customer ? trim($row->customer->first_name.' '.($row->customer->last_name ?? '')) : '—' }}</div>
                        <div class="text-xs text-zinc-500">
                            {{ $row->customerVehicle?->registration_no }}
                            @if ($row->customerVehicle?->model) · {{ trim(($row->customerVehicle->model->brand->name ?? '').' '.$row->customerVehicle->model->name) }} @endif
                            {{ $row->insuranceCompany ? ' · '.$row->insuranceCompany->name : '' }}
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">
                        @if ($row->jobCard)
                            <flux:link :href="route('job-card.edit', $row->job_card_id)" wire:navigate>{{ $row->jobCard->job_card_no }}</flux:link>
                        @else
                            —
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->department?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->advisor?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->driver?->name ?? '—' }}</flux:table.cell>
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
                    <flux:table.cell colspan="12" class="text-center text-zinc-500 py-12">
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
