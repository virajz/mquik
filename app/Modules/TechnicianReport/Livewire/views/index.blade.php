@php($fmt = fn ($m) => $m ? intdiv($m, 60).'h '.($m % 60).'m' : '—')
<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Technician Report</flux:heading>
            <flux:text class="mt-1">Working time, item hours, pause time and net TAT per technician (FWR).</flux:text>
        </div>
        @can('technician_report.export')
            <flux:button variant="primary" icon="arrow-down-tray" wire:click="download">Export CSV</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:select wire:model.live="technicianFilter" variant="listbox" searchable class="max-w-56">
            <flux:select.option value="all">All technicians</flux:select.option>
            @foreach ($this->technicians as $t)
                <flux:select.option :value="(string) $t->id">{{ $t->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:date-picker locale="en-IN" wire:model.live="fromDate" class="max-w-40" with-today selectable-header fixed-weeks type="input" />
        <flux:date-picker locale="en-IN" wire:model.live="toDate" class="max-w-40" with-today selectable-header fixed-weeks type="input" />
        <flux:checkbox wire:model.live="completedOnly" label="Completed orders only" />
        @if ($technicianFilter !== 'all' || $fromDate || $toDate || ! $completedOnly)
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Technician</flux:table.column>
            <flux:table.column class="w-28 text-end">Work Orders</flux:table.column>
            <flux:table.column class="w-28 text-end">Item Hours</flux:table.column>
            <flux:table.column class="w-32 text-end">Working Time</flux:table.column>
            <flux:table.column class="w-28 text-end">Pause</flux:table.column>
            <flux:table.column class="w-32 text-end">Net TAT</flux:table.column>
            <flux:table.column class="w-32 text-end">Avg Net TAT</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $r)
                <flux:table.row :key="$r['technician']">
                    <flux:table.cell class="font-medium">{{ $r['technician'] }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $r['orders'] }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ rtrim(rtrim(number_format($r['item_hours'], 2), '0'), '.') }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $fmt($r['gross_mins']) }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm text-zinc-500">{{ $fmt($r['pause_mins']) }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm font-medium">{{ $fmt($r['net_tat_mins']) }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $fmt($r['avg_net_tat_mins']) }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500 py-12">
                        <flux:icon.user-group class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No technician time recorded</div>
                        <flux:text class="mt-1">Completed final work orders with a technician and start/end times appear here.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
