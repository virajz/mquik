<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Job Cards</flux:heading>
            <flux:text class="mt-1">The central record — every vehicle in the workshop, who's working on it, where it stands.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('job_card.create')
                <flux:button variant="primary" icon="plus" :href="route('job-card.create')" wire:navigate>New Job Card</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by JC no, customer, phone, reg no..." icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="advisorFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All advisors</flux:select.option>
            @foreach ($this->employees as $e)
                <flux:select.option :value="(string) $e->id">{{ $e->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="technicianFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All technicians</flux:select.option>
            @foreach ($this->employees as $e)
                <flux:select.option :value="(string) $e->id">{{ $e->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="deptFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All depts</flux:select.option>
            @foreach ($this->departments as $d)
                <flux:select.option :value="(string) $d->id">{{ $d->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:date-picker wire:model.live="dateFrom" placeholder="From date" with-today selectable-header fixed-weeks type="input" clearable class="max-w-44" />
        <flux:date-picker wire:model.live="dateTo" placeholder="To date" with-today selectable-header fixed-weeks type="input" clearable class="max-w-44" />
        @if ($search || $statusFilter !== 'all' || $advisorFilter !== 'all' || $technicianFilter !== 'all' || $deptFilter !== 'all' || $dateFrom || $dateTo)
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'job_card_no'" :direction="$sortDirection" wire:click="sort('job_card_no')">JC No.</flux:table.column>
            <flux:table.column class="w-44" sortable :sorted="$sortBy === 'opened_at'" :direction="$sortDirection" wire:click="sort('opened_at')">Opened</flux:table.column>
            <flux:table.column>Customer / Vehicle</flux:table.column>
            <flux:table.column class="w-44">Advisor / Tech</flux:table.column>
            <flux:table.column class="w-20 text-center">Issues</flux:table.column>
            <flux:table.column class="w-44" sortable :sorted="$sortBy === 'promised_at'" :direction="$sortDirection" wire:click="sort('promised_at')">Promised</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->job_card_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div class="font-medium">{{ $row->opened_at?->format('d M Y') }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->opened_at?->format('h:i A') }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium">{{ trim($row->customer?->first_name.' '.($row->customer?->last_name ?? '')) }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 flex items-center gap-2 flex-wrap">
                            @if ($row->customer?->phone) <span class="font-mono">+91 {{ $row->customer->phone }}</span> @endif
                            @if ($row->customerVehicle)
                                <span>·</span>
                                <span class="font-mono">{{ $row->customerVehicle->registration_no }}</span>
                                <span>·</span>
                                <span>{{ trim(($row->customerVehicle->model?->brand?->name ?? '').' '.($row->customerVehicle->model?->name ?? '')) }}</span>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $row->advisor?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->technician?->name ?? '— unassigned —' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-center">
                        @if ($row->complaints_count > 0)
                            <flux:badge color="amber" size="sm">{{ $row->complaints_count }}</flux:badge>
                        @else
                            <span class="text-zinc-400 text-xs">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        @if ($row->promised_at)
                            <div class="font-medium">{{ $row->promised_at->format('d M Y') }}</div>
                            <div class="text-xs text-zinc-500 mt-0.5">{{ $row->promised_at->format('h:i A') }}</div>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($statusColor = match ($row->status) {
                            'open' => 'amber', 'in_progress' => 'blue', 'awaiting_parts' => 'sky',
                            'awaiting_approval' => 'purple', 'completed' => 'lime', 'closed' => 'zinc',
                            'cancelled' => 'red', default => 'zinc',
                        })
                        <flux:badge :color="$statusColor" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('job_card.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('job-card.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('job_card.delete')
                                <flux:modal.trigger :name="'job-card-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'job-card-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->job_card_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Cascades to complaints and inventory snapshot.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('job-card-delete-{{ $row->id }}').close()">Delete</flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center text-zinc-500 py-12">
                        <flux:icon.clipboard-document-list class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No job cards yet</div>
                        <flux:text class="mt-1">Open a card from an appointment, gate event, or directly here.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
