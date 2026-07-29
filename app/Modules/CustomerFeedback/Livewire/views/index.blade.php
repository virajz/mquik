<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Customer Feedback</flux:heading>
            <flux:text class="mt-1">Post-service ratings and feedback, with scheduled follow-up and CSI tracking.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('customer_feedback.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Feedback Export</flux:button>
            @endcan
            @can('customer_feedback.create')
                <flux:button variant="primary" icon="plus" :href="route('customer-feedback.create')" wire:navigate>New Feedback</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.star class="size-4" /><flux:text size="sm">Average Rating</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['avg_rating'] ?: '—' }}<span class="text-sm text-zinc-400"> / 5</span></div>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.chart-bar class="size-4" /><flux:text size="sm">Response Rate</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['response_rate'] }}<span class="text-sm text-zinc-400">%</span></div>
        </div>
        <button type="button" wire:click="$set('statusFilter', 'dissatisfied')"
            class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.exclamation-triangle class="size-4" /><flux:text size="sm">Negative Feedback</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums @if ($kpis['negative'] > 0) text-red-600 dark:text-red-400 @endif">{{ $kpis['negative'] }}</div>
        </button>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.inbox class="size-4" /><flux:text size="sm">Total Feedback</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['total'] }}</div>
        </div>
    </div>

    @if ($byAdvisor->isNotEmpty())
        <div class="mb-6 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <flux:text size="sm" class="font-medium text-zinc-500 mb-2">Advisor-wise Rating</flux:text>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1">
                @foreach ($byAdvisor as $a)
                    <div class="flex items-center justify-between text-sm py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span>{{ $a->advisor }}</span>
                        <span class="font-mono text-zinc-500">{{ $a->rating }} ★ <span class="text-zinc-400">({{ $a->responses }})</span></span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search feedback / invoice…" icon="magnifying-glass" clearable class="max-w-md" />
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
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'feedback_no'" :direction="$sortDirection" wire:click="sort('feedback_no')">Feedback</flux:table.column>
            <flux:table.column>Customer / Advisor</flux:table.column>
            <flux:table.column class="w-28 text-center" sortable :sorted="$sortBy === 'service_rating'" :direction="$sortDirection" wire:click="sort('service_rating')">Rating</flux:table.column>
            <flux:table.column class="w-40">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->feedback_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ trim(($row->customer?->first_name ?? '').' '.($row->customer?->last_name ?? '')) ?: '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->advisor?->name ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-center">
                        @if ($row->service_rating)
                            <span class="font-mono text-sm {{ $row->service_rating <= 2 ? 'text-red-600 dark:text-red-400' : '' }}">{{ $row->service_rating }} ★</span>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'satisfied', 'resolved' => 'lime',
                            'sent' => 'zinc',
                            'under_investigation' => 'amber',
                            'dissatisfied' => 'red',
                            'cancelled' => 'red',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('customer_feedback.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('customer-feedback.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('customer_feedback.delete')
                                <flux:modal.trigger :name="'cf-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'cf-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->feedback_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('cf-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.star class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No feedback yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
