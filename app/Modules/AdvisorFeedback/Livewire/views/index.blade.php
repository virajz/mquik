<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Advisor Feedback</flux:heading>
            <flux:text class="mt-1">The advisor's rating of the customer after a job.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('advisor_feedback.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Advisor Feedback Export</flux:button>
            @endcan
            @can('advisor_feedback.create')
                <flux:button variant="primary" icon="plus" :href="route('advisor-feedback.create')" wire:navigate>New Feedback</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-3">
        <button type="button" wire:click="$set('statusFilter', 'pending')"
            class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.clock class="size-4" /><flux:text size="sm">Pending</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['pending'] }}</div>
        </button>
        <button type="button" wire:click="$set('statusFilter', 'submitted')"
            class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.check-circle class="size-4" /><flux:text size="sm">Submitted</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['submitted'] }}</div>
        </button>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.star class="size-4" /><flux:text size="sm">Avg Customer Rating</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['avg_rating'] ?: '—' }}<span class="text-sm text-zinc-400"> / 5</span></div>
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search feedback / invoice…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'feedback_no'" :direction="$sortDirection" wire:click="sort('feedback_no')">Feedback</flux:table.column>
            <flux:table.column>Advisor / Customer</flux:table.column>
            <flux:table.column class="w-28 text-center">Avg</flux:table.column>
            <flux:table.column class="w-32">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->feedback_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $row->advisor?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ trim(($row->customer?->first_name ?? '').' '.($row->customer?->last_name ?? '')) }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-center font-mono text-sm">{{ $row->averageRating() ? $row->averageRating().' ★' : '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) { 'submitted' => 'lime', 'pending' => 'amber', default => 'red' })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('advisor_feedback.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('advisor-feedback.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('advisor_feedback.delete')
                                <flux:modal.trigger :name="'af-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'af-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->feedback_no }}?</flux:heading>
                                        <flux:text>This cannot be undone.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('af-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.user-circle class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No advisor feedback yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
