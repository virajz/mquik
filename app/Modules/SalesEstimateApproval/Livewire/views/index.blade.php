<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Sales Estimate Approval</flux:heading>
            <flux:text class="mt-1">Customer / advisor / insurer approval of estimates — line by line.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('sales_estimate_approval.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Analysis Report</flux:button>
            @endcan
            @can('sales_estimate_approval.create')
                <flux:button variant="primary" icon="plus" :href="route('sales-estimate-approval.create')" wire:navigate>New Approval</flux:button>
            @endcan
        </div>
    </div>

    {{-- Dashboard KPIs --}}
    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        <button type="button" wire:click="$set('authFilter', 'customer')"
            class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.user class="size-4" /><flux:text size="sm">Pending Customer</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['pending_customer'] }}</div>
        </button>
        <button type="button" wire:click="$set('authFilter', 'insurance')"
            class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.shield-check class="size-4" /><flux:text size="sm">Pending Insurance</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['pending_insurance'] }}</div>
        </button>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.check-circle class="size-4" /><flux:text size="sm">Approved Today</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['today_approved'] }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.calendar class="size-4" /><flux:text size="sm">Approved on Date</flux:text></div>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-semibold tabular-nums">{{ $kpis['custom_approved'] }}</span>
                <flux:date-picker locale="en-IN" wire:model.live="approvedOnDate" size="sm" class="max-w-36" with-today selectable-header fixed-weeks type="input" />
            </div>
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search approval / JC no…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-48">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="authFilter" variant="listbox" class="max-w-52">
            <flux:select.option value="all">All authorisation</flux:select.option>
            @foreach ($authorisations as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="companyFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All insurers</flux:select.option>
            @foreach ($this->companies as $co)
                <flux:select.option :value="(string) $co->id">{{ $co->name }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $authFilter !== 'all' || $companyFilter !== 'all' || $approvedOnDate)
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'approval_no'" :direction="$sortDirection" wire:click="sort('approval_no')">Approval</flux:table.column>
            <flux:table.column class="w-28">Job Card</flux:table.column>
            <flux:table.column class="w-32">Vehicle</flux:table.column>
            <flux:table.column>Authorisation</flux:table.column>
            <flux:table.column class="w-20 text-end">Lines</flux:table.column>
            <flux:table.column class="w-44">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->approval_no }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $row->jobCard?->job_card_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $row->customerVehicle?->registration_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $authorisations[$row->approval_authorisation] ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'fully_approved' => 'lime',
                            'partially_approved' => 'blue',
                            'rejected', 'cancelled', 'no_response' => 'red',
                            'query_raised', 'revised_resent' => 'amber',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('sales_estimate_approval.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('sales-estimate-approval.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('sales_estimate_approval.delete')
                                <flux:modal.trigger :name="'sea-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'sea-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->approval_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Approval lines are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('sea-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.clipboard-document-check class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No estimate approvals yet</div>
                        <flux:text class="mt-1">Send an estimate for customer / insurer approval.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
