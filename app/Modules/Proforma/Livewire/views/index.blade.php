<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Proformas</flux:heading>
            <flux:text class="mt-1">Pre-invoice proformas with warranty, approval workflow, insurance deductions and profitability.</flux:text>
        </div>
        @can('proforma.create')
            <flux:button variant="primary" icon="plus" :href="route('proforma.create')" wire:navigate>New Proforma</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by PF no, policy or reg no…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-56">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'proforma_no'" :direction="$sortDirection" wire:click="sort('proforma_no')">No.</flux:table.column>
            <flux:table.column>Customer / Vehicle</flux:table.column>
            <flux:table.column class="w-20 text-center">Lines</flux:table.column>
            <flux:table.column class="w-32 text-right" sortable :sorted="$sortBy === 'grand_total'" :direction="$sortDirection" wire:click="sort('grand_total')">Total</flux:table.column>
            <flux:table.column class="w-28 text-right" sortable :sorted="$sortBy === 'profit_total'" :direction="$sortDirection" wire:click="sort('profit_total')">Profit</flux:table.column>
            <flux:table.column class="w-44" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->proforma_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="text-sm font-medium">{{ trim($row->customer?->first_name.' '.($row->customer?->last_name ?? '')) }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 flex items-center gap-2">
                            @if ($row->customerVehicle)<span class="font-mono">{{ $row->customerVehicle->registration_no }}</span>@endif
                            @if ($row->insuranceCompany)<span>· {{ $row->insuranceCompany->name }}</span>@endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="text-center font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm">{{ number_format((float) $row->grand_total, 2) }}</flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm {{ (float) $row->profit_total < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ number_format((float) $row->profit_total, 2) }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'draft' => 'zinc', 'wip' => 'indigo', 'ready' => 'sky', 'sent_customer','sent_insurance' => 'blue',
                            'under_review' => 'amber', 'approved' => 'lime', 'rejected' => 'red', 'converted' => 'green', 'cancelled' => 'zinc', default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('proforma.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('proforma.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('proforma.delete')
                                <flux:modal.trigger :name="'pf-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'pf-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->proforma_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Cascades to lines, deductions and attachments.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('pf-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.document-currency-rupee class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No proformas yet</div>
                        <flux:text class="mt-1">Generate one from an estimate or job card.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
