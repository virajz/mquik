<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Claim Intimation</flux:heading>
            <flux:text class="mt-1">Accidental insurance claims — policy, claim type, intimation and survey TAT.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('claim_intimation.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Survey Report</flux:button>
            @endcan
            @can('claim_intimation.create')
                <flux:button variant="primary" icon="plus" :href="route('claim-intimation.create')" wire:navigate>New Claim</flux:button>
            @endcan
        </div>
    </div>

    {{-- Dashboard counters --}}
    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ([
            ['label' => 'Pending', 'value' => $kpis['pending'], 'icon' => 'clock', 'filter' => 'pending'],
            ['label' => 'Intimated', 'value' => $kpis['intimated'], 'icon' => 'paper-airplane', 'filter' => 'intimated'],
            ['label' => 'Awaiting Survey', 'value' => $kpis['awaiting_survey'], 'icon' => 'eye', 'filter' => 'intimated'],
            ['label' => 'Cancelled', 'value' => $kpis['cancelled'], 'icon' => 'x-circle', 'filter' => 'cancelled'],
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

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search intimation / policy / claim / JC no…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="tatFilter" variant="listbox" class="max-w-52">
            <flux:select.option value="all">All survey TAT</flux:select.option>
            @foreach ($surveyTats as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="companyFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All insurers</flux:select.option>
            @foreach ($this->companies as $co)
                <flux:select.option :value="(string) $co->id">{{ $co->name }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $tatFilter !== 'all' || $companyFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'intimation_no'" :direction="$sortDirection" wire:click="sort('intimation_no')">Intimation</flux:table.column>
            <flux:table.column class="w-28">Job Card</flux:table.column>
            <flux:table.column class="w-32">Vehicle</flux:table.column>
            <flux:table.column>Insurer</flux:table.column>
            <flux:table.column class="w-40">Survey TAT</flux:table.column>
            <flux:table.column class="w-32">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->intimation_no }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $row->jobCard?->job_card_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $row->customerVehicle?->registration_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $row->insuranceCompany?->name ?? '—' }}</div>
                        @if ($row->claimType)
                            <div class="text-xs text-zinc-500 mt-0.5">{{ $row->claimType->name }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $surveyTats[$row->survey_tat] ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'intimated' => 'lime',
                            'cancelled' => 'red',
                            default => 'amber',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('claim_intimation.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('claim-intimation.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('claim_intimation.delete')
                                <flux:modal.trigger :name="'ci-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'ci-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->intimation_no }}?</flux:heading>
                                        <flux:text>This cannot be undone.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('ci-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.shield-exclamation class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No claim intimations yet</div>
                        <flux:text class="mt-1">Register an accidental claim to notify the surveyor.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
