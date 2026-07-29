<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Service Recommendation Follow-Ups</flux:heading>
            <flux:text class="mt-1">Follow up on technician-recommended future services.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('service_recommendation_follow_up.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Recommended Follow-Up Report</flux:button>
            @endcan
            @can('service_recommendation_follow_up.create')
                <flux:button variant="primary" icon="plus" :href="route('service-recommendation-follow-up.create')" wire:navigate>New Recommendation</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach ([
            ['label' => 'Open Follow-Ups', 'value' => $kpis['open'], 'icon' => 'clock'],
            ["label" => "Today's Follow-Ups", 'value' => $kpis['today'], 'icon' => 'phone'],
            ['label' => 'Upcoming', 'value' => $kpis['upcoming'], 'icon' => 'calendar-days'],
            ['label' => 'Safety-Critical', 'value' => $kpis['safety'], 'icon' => 'shield-exclamation', 'danger' => true],
            ['label' => 'Conversion', 'value' => $kpis['conversion_rate'].'%', 'icon' => 'chart-bar'],
            ['label' => 'Lost', 'value' => $kpis['lost'], 'icon' => 'x-circle', 'danger' => true],
        ] as $kpi)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-xl font-semibold tabular-nums @if (($kpi['danger'] ?? false) && (int) $kpi['value'] > 0) text-red-600 dark:text-red-400 @endif">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search recommendation / service…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-52">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="categoryFilter" variant="listbox" class="max-w-48">
            <flux:select.option value="all">All categories</flux:select.option>
            @foreach ($categories as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $categoryFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'recommendation_no'" :direction="$sortDirection" wire:click="sort('recommendation_no')">Rec.</flux:table.column>
            <flux:table.column>Customer / Service</flux:table.column>
            <flux:table.column class="w-24">Priority</flux:table.column>
            <flux:table.column class="w-44">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->recommendation_no }}
                        @if ($row->recommendation_category === 'safety')<flux:badge size="sm" color="red" class="ml-1">Safety</flux:badge>@endif
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ trim(($row->customer?->first_name ?? '').' '.($row->customer?->last_name ?? '')) ?: '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->recommended_service ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($pc = match ($row->priority) { 'high' => 'red', 'medium' => 'amber', default => 'zinc' })
                        <flux:badge :color="$pc" size="sm">{{ ucfirst($row->priority) }}</flux:badge>
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
                            @can('service_recommendation_follow_up.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('service-recommendation-follow-up.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('service_recommendation_follow_up.delete')
                                <flux:modal.trigger :name="'srf-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'srf-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->recommendation_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('srf-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.wrench-screwdriver class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No recommendations yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
