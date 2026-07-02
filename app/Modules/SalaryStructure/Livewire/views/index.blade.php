<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Salary Structures</flux:heading>
            <flux:text class="mt-1">Per-employee salary structure — basic + earning / deduction components with computed gross, deductions and net.</flux:text>
        </div>
        @can('salary_structure.create')
            <flux:button variant="primary" icon="plus" :href="route('salary-structure.create')" wire:navigate>New Structure</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:select wire:model.live="employeeFilter" variant="listbox" searchable class="max-w-56">
            <flux:select.option value="all">All employees</flux:select.option>
            @foreach ($this->employees as $e)
                <flux:select.option :value="(string) $e->id">{{ $e->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($employeeFilter !== 'all' || $statusFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">ID</flux:table.column>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'effective_from'" :direction="$sortDirection" wire:click="sort('effective_from')">Effective From</flux:table.column>
            <flux:table.column class="w-20 text-center">Lines</flux:table.column>
            <flux:table.column class="w-36 text-right" sortable :sorted="$sortBy === 'net_salary'" :direction="$sortDirection" wire:click="sort('net_salary')">Net Salary</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">#{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="text-sm font-medium">{{ $row->employee?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 font-mono">{{ $row->employee?->employee_code }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->effective_from?->format('M Y') }}</flux:table.cell>
                    <flux:table.cell class="text-center font-mono text-sm">{{ $row->lines_count }}</flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm font-medium">{{ number_format((float) $row->net_salary, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) { 'active' => 'lime', 'superseded' => 'zinc', default => 'sky' })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('salary_structure.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('salary-structure.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('salary_structure.delete')
                                <flux:modal.trigger :name="'ss-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'ss-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete salary structure #{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}?</flux:heading>
                                        <flux:text>Cannot be undone. Cascades to its component lines.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('ss-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.calculator class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No salary structures yet</div>
                        <flux:text class="mt-1">Build a structure per employee from the salary components.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
