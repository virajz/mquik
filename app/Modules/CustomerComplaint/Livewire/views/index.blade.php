<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Customer Complaints</flux:heading>
            <flux:text class="mt-1">Register and track resolution of customer / internal complaints.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('customer_complaint.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Complaint Report</flux:button>
            @endcan
            @can('customer_complaint.create')
                <flux:button variant="primary" icon="plus" :href="route('customer-complaint.create')" wire:navigate>New Complaint</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.inbox class="size-4" /><flux:text size="sm">Complaints Received</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['received'] }}</div>
        </div>
        <button type="button" wire:click="$set('statusFilter', 'under_investigation')"
            class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.clock class="size-4" /><flux:text size="sm">Open Complaints</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['open'] }}</div>
        </button>
        <button type="button" wire:click="$set('statusFilter', 'resolved')"
            class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.check-circle class="size-4" /><flux:text size="sm">Resolved</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['resolved'] }}</div>
        </button>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.star class="size-4" /><flux:text size="sm">Avg Satisfaction</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['avg_score'] ?: '—' }}<span class="text-sm text-zinc-400"> / 5</span></div>
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search complaint / invoice…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-56">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="typeFilter" variant="listbox" class="max-w-56">
            <flux:select.option value="all">All types</flux:select.option>
            @foreach ($types as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $typeFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'complaint_no'" :direction="$sortDirection" wire:click="sort('complaint_no')">Complaint</flux:table.column>
            <flux:table.column>Customer / Type</flux:table.column>
            <flux:table.column class="w-24">Priority</flux:table.column>
            <flux:table.column class="w-48">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->complaint_no }}
                        @if ($row->complaint_type === 'repeat_job')<flux:badge size="sm" color="red" class="ml-1">Repeat</flux:badge>@endif
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ trim(($row->customer?->first_name ?? '').' '.($row->customer?->last_name ?? '')) ?: '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $types[$row->complaint_type] ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($pc = match ($row->priority) { 'high' => 'red', 'medium' => 'amber', default => 'zinc' })
                        <flux:badge :color="$pc" size="sm">{{ ucfirst($row->priority) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'resolved' => 'lime',
                            'under_investigation' => 'amber',
                            'pending_customer_response', 'pending_vendor_response' => 'sky',
                            'rejected', 'cancelled' => 'red',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('customer_complaint.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('customer-complaint.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('customer_complaint.delete')
                                <flux:modal.trigger :name="'cc-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'cc-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->complaint_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('cc-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.chat-bubble-left-ellipsis class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No complaints yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
