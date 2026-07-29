@php($fmt = fn ($m) => $m ? intdiv($m, 60).'h '.($m % 60).'m' : '—')
<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Queue Management</flux:heading>
            <flux:text class="mt-1">Live service queue board — car wash, alignment, PDI, detailing.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('queue_management.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Export</flux:button>
            @endcan
            @can('queue_management.create')
                <flux:button variant="primary" icon="plus" :href="route('queue-management.create')" wire:navigate>Add to Queue</flux:button>
            @endcan
        </div>
    </div>

    {{-- Dashboard KPIs --}}
    <div class="mb-6 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        @foreach ([
            ['label' => 'Pending', 'value' => $kpis['pending'], 'icon' => 'clock'],
            ['label' => 'Completed', 'value' => $kpis['completed'], 'icon' => 'check-circle'],
            ['label' => 'Total', 'value' => $kpis['total'], 'icon' => 'queue-list'],
            ['label' => 'Avg Waiting', 'value' => $fmt($kpis['avg_wait']), 'icon' => 'hourglass'],
            ['label' => 'Avg Washing', 'value' => $fmt($kpis['avg_wash']), 'icon' => 'sparkles'],
            ['label' => 'On-Time %', 'value' => $kpis['on_time_pct'].'%', 'icon' => 'bolt'],
        ] as $kpi)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center gap-2 text-zinc-500">
                    <flux:icon :name="$kpi['icon'] === 'hourglass' ? 'clock' : $kpi['icon']" class="size-4" />
                    <flux:text size="sm">{{ $kpi['label'] }}</flux:text>
                </div>
                <div class="mt-1 text-xl font-semibold tabular-nums">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search queue no / reg / job desc…" icon="magnifying-glass" clearable class="max-w-sm" />
        <flux:select wire:model.live="viewFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All screens</flux:select.option>
            @foreach ($screenViews as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="typeFilter" variant="listbox" class="max-w-52">
            <flux:select.option value="all">All types</flux:select.option>
            @foreach ($queueTypes as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $viewFilter !== 'all' || $typeFilter !== 'all' || $statusFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-24">Queue No</flux:table.column>
            <flux:table.column class="w-32">Reg / Model</flux:table.column>
            <flux:table.column>Type / Job</flux:table.column>
            <flux:table.column>Technician</flux:table.column>
            <flux:table.column class="w-40">Promised / Expected</flux:table.column>
            <flux:table.column class="w-32">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">
                        {{ $row->queue_no }}
                        @if ($row->is_high_priority)
                            <flux:badge color="red" size="sm" class="ml-1">HP</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="font-mono text-xs">{{ $row->customerVehicle?->registration_no ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->customerVehicle?->model?->name }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $queueTypes[$row->queue_type] ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->job_description ?? $row->labour?->name }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $row->technician?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-xs text-zinc-500">
                        <div>{{ $row->promised_delivery_at?->format('d M, H:i') ?? '—' }}</div>
                        <div>{{ $row->expected_completion_at?->format('d M, H:i') ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'completed' => 'lime',
                            'ready' => 'green',
                            'cancelled' => 'red',
                            'in_progress' => 'blue',
                            'on_hold' => 'amber',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('queue_management.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('queue-management.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('queue_management.delete')
                                <flux:modal.trigger :name="'sq-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'sq-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Remove {{ $row->queue_no }}?</flux:heading>
                                        <flux:text>This cannot be undone.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('sq-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.queue-list class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">Queue is empty</div>
                        <flux:text class="mt-1">Add a vehicle to a service queue.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
