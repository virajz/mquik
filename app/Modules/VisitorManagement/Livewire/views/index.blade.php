<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Visitor Management (VMS)</flux:heading>
            <flux:text class="mt-1">Reception queue and token system — walk-in / appointment visits.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('visitor_management.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Visitor Management Report</flux:button>
            @endcan
            @can('visitor_management.create')
                <flux:button variant="primary" icon="plus" :href="route('visitor-management.create')" wire:navigate>New Visit</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ([
            ['label' => 'Tokens Today', 'value' => $kpis['tokens_today'], 'icon' => 'identification'],
            ['label' => 'Served Today', 'value' => $kpis['served_today'], 'icon' => 'check-circle'],
            ['label' => 'No-Shows', 'value' => $kpis['no_shows'], 'icon' => 'x-circle', 'danger' => true],
            ['label' => 'Avg Wait (min)', 'value' => $kpis['avg_wait_minutes'], 'icon' => 'clock'],
        ] as $kpi)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-2xl font-semibold tabular-nums @if (($kpi['danger'] ?? false) && $kpi['value'] > 0) text-red-600 dark:text-red-400 @endif">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search token…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-56">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'token_no'" :direction="$sortDirection" wire:click="sort('token_no')">Token</flux:table.column>
            <flux:table.column>Customer / Advisor</flux:table.column>
            <flux:table.column class="w-40" sortable :sorted="$sortBy === 'arrival_at'" :direction="$sortDirection" wire:click="sort('arrival_at')">Arrival</flux:table.column>
            <flux:table.column class="w-48">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->token_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ trim(($row->customer?->first_name ?? '').' '.($row->customer?->last_name ?? '')) ?: '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->assignedTo?->name ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->arrival_at?->format('d M Y, H:i') ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'job_card_created' => 'lime',
                            'consultation_completed' => 'blue',
                            'consultation_started' => 'sky',
                            'advisor_assigned' => 'amber',
                            'cancelled' => 'red',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('visitor_management.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('visitor-management.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('visitor_management.delete')
                                <flux:modal.trigger :name="'vms-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'vms-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->token_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('vms-delete-{{ $row->id }}').close()">Delete</flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-500 py-12">
                        <flux:icon.identification class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No visits yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
