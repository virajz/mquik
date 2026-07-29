<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Vehicle AMC</flux:heading>
            <flux:text class="mt-1">Manage AMC packages, included services and expiry.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('vehicle_amc.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">AMC Report</flux:button>
            @endcan
            @can('vehicle_amc.create')
                <flux:button variant="primary" icon="plus" :href="route('vehicle-amc.create')" wire:navigate>New AMC</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ([
            ['label' => 'Active AMC', 'value' => $kpis['active'], 'icon' => 'identification'],
            ['label' => 'Renewals Due', 'value' => $kpis['renewals_due'], 'icon' => 'calendar-days', 'danger' => true],
            ['label' => 'Services Availed', 'value' => $kpis['services_availed'], 'icon' => 'wrench-screwdriver'],
            ['label' => 'AMC Revenue', 'value' => '₹'.number_format($kpis['revenue'], 0), 'icon' => 'banknotes'],
        ] as $kpi)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-2xl font-semibold tabular-nums @if (($kpi['danger'] ?? false) && (int) $kpi['value'] > 0) text-amber-600 dark:text-amber-400 @endif">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search AMC no…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="packageFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All packages</flux:select.option>
            @foreach ($packages as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $packageFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-40" sortable :sorted="$sortBy === 'amc_no'" :direction="$sortDirection" wire:click="sort('amc_no')">AMC No</flux:table.column>
            <flux:table.column>Customer / Vehicle</flux:table.column>
            <flux:table.column class="w-24">Package</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'end_date'" :direction="$sortDirection" wire:click="sort('end_date')">Expiry</flux:table.column>
            <flux:table.column class="w-28">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                @php($due = $row->status === 'active' && $row->end_date && $row->end_date->between($today, $today->copy()->addDays(7)))
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->amc_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ trim(($row->customer?->first_name ?? '').' '.($row->customer?->last_name ?? '')) ?: '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 font-mono">{{ $row->customerVehicle?->registration_no ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($pc = match ($row->amc_package) { 'platinum' => 'zinc', 'gold' => 'amber', 'silver' => 'sky', default => 'zinc' })
                        <flux:badge :color="$pc" size="sm">{{ $packages[$row->amc_package] ?? '—' }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm {{ $due ? 'text-amber-600 dark:text-amber-400 font-medium' : '' }}">{{ $row->end_date?->format('d M Y') ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'active' => 'lime',
                            'renewed' => 'blue',
                            'expired' => 'amber',
                            'lost', 'cancelled' => 'red',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('vehicle_amc.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('vehicle-amc.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('vehicle_amc.delete')
                                <flux:modal.trigger :name="'amc-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'amc-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->amc_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Included items and attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('amc-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.identification class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No AMC contracts yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
