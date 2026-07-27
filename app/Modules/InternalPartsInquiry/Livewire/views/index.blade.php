<div>
    {{-- Page heading --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Internal Parts Inquiry</flux:heading>
            <flux:text class="mt-1">Advisor asks the store for part rate, availability and brand.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            @can('internal_parts_inquiry.create')
                <flux:button variant="primary" icon="plus" :href="route('internal-parts-inquiry.create')" wire:navigate>
                    New Inquiry
                </flux:button>
            @endcan

            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />
                <flux:menu>
                    <flux:menu.item icon="arrow-up-tray"
                        wire:click="$dispatch('start-import', { module: 'InternalPartsInquiry' })">
                        Import…
                    </flux:menu.item>
                    <flux:menu.item icon="arrow-down-tray"
                        wire:click="$dispatch('start-export', { module: 'InternalPartsInquiry' })">
                        Export
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    {{-- Export/Import engines --}}
    <livewire:import-export.export-button :module="'InternalPartsInquiry'" wire:key="export-ipi" />
    <livewire:import-export.import-wizard :module="'InternalPartsInquiry'" wire:key="import-ipi" />

    {{-- Dashboard counters --}}
    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ([
            ['label' => 'Open Inquiries', 'value' => $kpis['open'], 'icon' => 'inbox', 'filter' => 'all'],
            ['label' => 'Pending', 'value' => $kpis['pending'], 'icon' => 'clock', 'filter' => 'pending'],
            ['label' => 'Ordered', 'value' => $kpis['ordered'], 'icon' => 'truck', 'filter' => 'ordered'],
            ['label' => 'Cancelled / N/A', 'value' => $kpis['cancelled'], 'icon' => 'x-circle', 'filter' => 'cancelled'],
        ] as $kpi)
            <button type="button" wire:click="$set('statusFilter', '{{ $kpi['filter'] }}')"
                class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
                <div class="flex items-center gap-2 text-zinc-500">
                    <flux:icon :name="$kpi['icon']" class="size-4" />
                    <flux:text size="sm">{{ $kpi['label'] }}</flux:text>
                </div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpi['value'] }}</div>
            </button>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by IPI no or job card no…"
            icon="magnifying-glass"
            clearable
            class="max-w-sm"
        />

        <flux:select wire:model.live="statusFilter" variant="listbox" class="w-48">
            <flux:select.option value="all">All statuses</flux:select.option>
            @foreach ($statuses as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="typeFilter" variant="listbox" class="w-48">
            <flux:select.option value="all">All types</flux:select.option>
            @foreach ($inquiryTypes as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="requestedByFilter" variant="listbox" searchable class="w-48">
            <flux:select.option value="all">All requesters</flux:select.option>
            @foreach ($this->employees as $emp)
                <flux:select.option :value="(string) $emp->id">{{ $emp->name }}</flux:select.option>
            @endforeach
        </flux:select>

        @if ($search !== '' || $statusFilter !== 'all' || $typeFilter !== 'all' || $requestedByFilter !== 'all' || $targetFilter !== 'all')
            <flux:button variant="ghost" size="sm" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'ipi_no'" :direction="$sortDirection" wire:click="sort('ipi_no')">
                IPI No
            </flux:table.column>
            <flux:table.column>Job Card</flux:table.column>
            <flux:table.column>Requested By</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'requested_at'" :direction="$sortDirection" wire:click="sort('requested_at')">
                Requested At
            </flux:table.column>
            <flux:table.column class="text-end">Parts</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">
                Status
            </flux:table.column>
            <flux:table.column class="w-28" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs font-medium">
                        {{ $row->ipi_no ?? '—' }}
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        {{ $row->jobCard?->job_card_no ?? '—' }}
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        {{ $row->requestedBy?->name ?? '—' }}
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500 text-sm">
                        {{ $row->requested_at?->format('d M Y H:i') }}
                    </flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">
                        {{ $row->items_count }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($badge = match ($row->status) {
                            'completed', 'fully_available' => 'lime',
                            'cancelled', 'not_available' => 'red',
                            'ordered', 'in_progress' => 'blue',
                            'partially_available', 'alternative_suggested' => 'amber',
                            default => 'zinc',
                        })
                        <flux:badge :color="$badge" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('internal_parts_inquiry.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('internal-parts-inquiry.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('internal_parts_inquiry.delete')
                                <flux:modal.trigger :name="'ipi-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>

                                <flux:modal :name="'ipi-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->ipi_no }}?</flux:heading>
                                        <flux:text>This will also delete all parts and attachments on this inquiry. This cannot be undone.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close>
                                                <flux:button variant="ghost">Cancel</flux:button>
                                            </flux:modal.close>
                                            <flux:button variant="danger"
                                                wire:click="delete({{ $row->id }})"
                                                x-on:click="$flux.modal('ipi-delete-{{ $row->id }}').close()">
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
                    <flux:table.cell colspan="7" class="text-center text-zinc-500 py-12">
                        <flux:icon.inbox class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No inquiries yet</div>
                        <flux:text class="mt-1">Raise one to check a part's rate, availability and brand with the store.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Pagination --}}
    @if ($rows->hasPages())
        <div class="mt-4">
            <flux:pagination :paginator="$rows" />
        </div>
    @endif
</div>
