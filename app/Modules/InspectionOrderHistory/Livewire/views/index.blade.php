<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">VIO History</flux:heading>
            <flux:text class="mt-1">Every vehicle inspection order — technician, bay, status and turnaround.</flux:text>
        </div>
        @can('inspection_order_history.export')
            <flux:button variant="primary" icon="arrow-down-tray" wire:click="download">Export CSV</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search VIO no or job card…" icon="magnifying-glass" clearable class="max-w-sm" />

        <flux:select wire:model.live="technicianFilter" variant="listbox" searchable clearable :filter="false" placeholder="All technicians" class="max-w-52">
            <x-slot name="search">
                <flux:select.search wire:model.live.debounce.250ms="technicianSearch" placeholder="Type a name…" />
            </x-slot>
            <flux:select.option value="all">All technicians</flux:select.option>
            @foreach ($this->technicians as $t)
                <flux:select.option :value="(string) $t->id" wire:key="tech-{{ $t->id }}">{{ $t->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="bayFilter" variant="listbox" clearable placeholder="All bays" class="max-w-40">
            <flux:select.option value="all">All bays</flux:select.option>
            @foreach ($this->bays as $b)
                <flux:select.option :value="(string) $b->id" wire:key="bay-{{ $b->id }}">{{ $b->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="statusFilter" variant="listbox" clearable placeholder="All statuses" class="max-w-44">
            <flux:select.option value="all">All statuses</flux:select.option>
            @foreach ($this->statuses as $key => $label)
                <flux:select.option :value="$key" wire:key="st-{{ $key }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input type="date" wire:model.live="fromDate" class="max-w-40" />
        <flux:input type="date" wire:model.live="toDate" class="max-w-40" />

        @if ($search || $technicianFilter !== 'all' || $bayFilter !== 'all' || $statusFilter !== 'all' || $fromDate || $toDate)
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28">VIO No</flux:table.column>
            <flux:table.column class="w-28">Job Card</flux:table.column>
            <flux:table.column class="w-32">Vehicle</flux:table.column>
            <flux:table.column>Technician</flux:table.column>
            <flux:table.column class="w-24">Bay</flux:table.column>
            <flux:table.column class="w-24">Priority</flux:table.column>
            <flux:table.column class="w-32">Status</flux:table.column>
            <flux:table.column class="w-28">TAT</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($paginator as $order)
                <flux:table.row :key="$order->id">
                    <flux:table.cell class="font-mono text-xs">{{ $order->order_no }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $order->jobCard?->job_card_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $order->jobCard?->customerVehicle?->registration_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium text-sm">{{ $order->technician?->name ?? '—' }}</div>
                        @if ($order->advisor)
                            <div class="text-xs text-zinc-500 mt-0.5">Advisor: {{ $order->advisor->name }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $order->bay?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $order->priority?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($order->status) {
                            'completed' => 'lime',
                            'cancelled' => 'red',
                            'on_hold' => 'amber',
                            'wip' => 'blue',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $this->statuses[$order->status] ?? $order->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $tat($order) ?? '—' }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center text-zinc-500 py-12">
                        <flux:icon.clock class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No inspection orders match these filters</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($paginator->hasPages())<div class="mt-4"><flux:pagination :paginator="$paginator" /></div>@endif
</div>
