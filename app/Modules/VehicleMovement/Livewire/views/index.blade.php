<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Vehicle Inward / Outward</flux:heading>
            <flux:text class="mt-1">Gate log of vehicles entering and leaving — slot, gate, driver, number plate and TAT.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('vehicle_movement.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Inward / Outward Register</flux:button>
            @endcan
            @can('vehicle_movement.create')
                <flux:button variant="primary" icon="plus" :href="route('vehicle-movement.create')" wire:navigate>Log Movement</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach ([
            ['label' => 'Vehicles Inward Today', 'value' => $kpis['inward_today'], 'icon' => 'arrows-right-left', 'type' => 'inward'],
            ['label' => 'Trial Runs Today', 'value' => $kpis['trial_today'], 'icon' => 'bolt', 'type' => null],
            ['label' => 'Vehicles Outward Today', 'value' => $kpis['outward_today'], 'icon' => 'truck', 'type' => 'outward'],
        ] as $kpi)
            <button type="button" @if ($kpi['type']) wire:click="$set('typeFilter', '{{ $kpi['type'] }}')" @else disabled @endif
                class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 @if ($kpi['type']) hover:border-zinc-400 dark:hover:border-zinc-500 transition @endif">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpi['value'] }}</div>
            </button>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search movement / number plate…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="typeFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">Inward & Outward</flux:select.option>
            @foreach ($movementTypes as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="jobFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All jobs</flux:select.option>
            @foreach ($jobStatuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $typeFilter !== 'all' || $jobFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'movement_no'" :direction="$sortDirection" wire:click="sort('movement_no')">Movement</flux:table.column>
            <flux:table.column>Vehicle / Plate</flux:table.column>
            <flux:table.column class="w-40" sortable :sorted="$sortBy === 'entry_at'" :direction="$sortDirection" wire:click="sort('entry_at')">Entry / Exit</flux:table.column>
            <flux:table.column class="w-24">TAT</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->movement_no }}
                        <flux:badge size="sm" :color="$row->movement_type === 'outward' ? 'amber' : 'sky'" class="ml-1">{{ $movementTypes[$row->movement_type] ?? $row->movement_type }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div class="font-mono">{{ $row->number_plate ?? $row->customerVehicle?->registration_no ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->jobCard?->job_card_no ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-xs text-zinc-500">
                        <div>{{ $row->entry_at?->format('d/m, H:i') ?? '—' }}</div>
                        <div>{{ $row->exit_at?->format('d/m, H:i') ?? '—' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="font-mono text-sm">{{ $row->tatLabel() ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('vehicle_movement.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('vehicle-movement.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('vehicle_movement.delete')
                                <flux:modal.trigger :name="'vm-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'vm-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->movement_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Photos are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('vm-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.arrows-right-left class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No vehicle movements yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
