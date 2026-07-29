<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Service Due Follow-Ups</flux:heading>
            <flux:text class="mt-1">Follow-ups and tracking for upcoming scheduled services.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('service_due_follow_up.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Follow-Up Report</flux:button>
            @endcan
            @can('service_due_follow_up.create')
                <flux:button variant="primary" icon="plus" :href="route('service-due-follow-up.create')" wire:navigate>New Follow-Up</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach ([
            ['label' => 'Due Today', 'value' => $kpis['due_today'], 'icon' => 'calendar-days'],
            ['label' => 'Upcoming Due', 'value' => $kpis['upcoming'], 'icon' => 'clock'],
            ['label' => 'Overdue', 'value' => $kpis['overdue'], 'icon' => 'exclamation-triangle', 'danger' => true],
            ['label' => 'Appointments', 'value' => $kpis['appointments'], 'icon' => 'check-circle'],
            ['label' => 'Lost', 'value' => $kpis['lost'], 'icon' => 'x-circle', 'danger' => true],
            ['label' => 'Recovered', 'value' => $kpis['recovered'], 'icon' => 'arrow-uturn-left'],
        ] as $kpi)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-2xl font-semibold tabular-nums @if (($kpi['danger'] ?? false) && $kpi['value'] > 0) text-red-600 dark:text-red-400 @endif">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search follow-up / vehicle…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-52">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="retentionFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All retention</flux:select.option>
            @foreach ($retentions as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $retentionFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'follow_up_no'" :direction="$sortDirection" wire:click="sort('follow_up_no')">Follow-up</flux:table.column>
            <flux:table.column>Customer / Vehicle</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'due_date'" :direction="$sortDirection" wire:click="sort('due_date')">Due Date</flux:table.column>
            <flux:table.column class="w-44">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                @php($overdue = $row->due_date && $row->due_date->lt($today) && in_array($row->status, \App\Modules\ServiceDueFollowUp\Models\ServiceDueFollowUp::openStatuses(), true))
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->follow_up_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ trim(($row->customer?->first_name ?? '').' '.($row->customer?->last_name ?? '')) ?: '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 font-mono">{{ $row->customerVehicle?->registration_no ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm {{ $overdue ? 'text-red-600 dark:text-red-400 font-medium' : '' }}">
                        {{ $row->due_date?->format('d M Y') ?? '—' }}
                        @if ($overdue)<flux:icon.exclamation-triangle class="inline size-3 -mt-0.5 ml-0.5" />@endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'converted' => 'lime',
                            'appointment_booked', 'accepted' => 'blue',
                            'quotation_sent', 'informed', 'assigned' => 'sky',
                            'pending' => 'amber',
                            'lost_opportunity' => 'red',
                            'cancelled' => 'zinc',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('service_due_follow_up.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('service-due-follow-up.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('service_due_follow_up.delete')
                                <flux:modal.trigger :name="'sdf-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'sdf-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->follow_up_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('sdf-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.calendar-days class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No service due follow-ups yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
