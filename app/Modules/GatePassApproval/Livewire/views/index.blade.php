<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Gate Pass Approval</flux:heading>
            <flux:text class="mt-1">Approve vehicle delivery against outstanding — credit, post-dated cheque or without DO.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('gate_pass_approval.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Gate Pass Report</flux:button>
            @endcan
            @can('gate_pass_approval.create')
                <flux:button variant="primary" icon="plus" :href="route('gate-pass-approval.create')" wire:navigate>New Approval</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-3">
        <button type="button" wire:click="$set('statusFilter', 'requested')"
            class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.clock class="size-4" /><flux:text size="sm">Pending Gate Pass Approvals</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['pending'] }}</div>
        </button>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.truck class="size-4" /><flux:text size="sm">Delivered with Outstanding</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['delivered_outstanding'] }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.banknotes class="size-4" /><flux:text size="sm">Outstanding Amount Pending</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums @if ($kpis['outstanding_value'] > 0) text-red-600 dark:text-red-400 @endif">₹{{ number_format($kpis['outstanding_value'], 0) }}</div>
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search approval / job card…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-56">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="authorityFilter" variant="listbox" class="max-w-52">
            <flux:select.option value="all">All authorities</flux:select.option>
            @foreach ($authorities as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $authorityFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'approval_no'" :direction="$sortDirection" wire:click="sort('approval_no')">Approval</flux:table.column>
            <flux:table.column>Customer / Vehicle</flux:table.column>
            <flux:table.column class="w-32 text-end" sortable :sorted="$sortBy === 'outstanding_amount'" :direction="$sortDirection" wire:click="sort('outstanding_amount')">Outstanding</flux:table.column>
            <flux:table.column class="w-48">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->approval_no }}
                        @if ($row->approval_authority === 'admin_hr_owner')<flux:badge size="sm" color="purple" class="ml-1">Admin</flux:badge>@endif
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ trim(($row->customer?->first_name ?? '').' '.($row->customer?->last_name ?? '')) ?: '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 font-mono">{{ $row->customerVehicle?->registration_no ?? $row->jobCard?->job_card_no ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->outstanding_amount !== null ? number_format((float) $row->outstanding_amount, 2) : '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'full_pending_approved', 'partial_pending_approved' => 'lime',
                            'under_review', 'requested' => 'amber',
                            'on_hold' => 'orange',
                            'rejected', 'cancelled' => 'red',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('gate_pass_approval.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('gate-pass-approval.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('gate_pass_approval.delete')
                                <flux:modal.trigger :name="'gpa-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'gpa-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->approval_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('gpa-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.shield-check class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No gate pass approvals yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
