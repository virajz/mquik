@use(App\Modules\SalaryStructure\Models\SalaryStructure)
<div class="max-w-5xl">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:link :href="route('salary-structure.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Salary Structures
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? 'Salary Structure #'.$editingId : 'New Salary Structure' }}</flux:heading>
        </div>
        @php($t = $this->totals)
        <div class="flex items-center gap-2">
            <flux:badge size="lg" class="font-mono">Net ₹ {{ number_format($t['net'], 2) }}</flux:badge>
            @if ($editingId)
                <flux:badge :color="match ($status) { 'active' => 'lime', 'superseded' => 'zinc', default => 'sky' }" size="lg">{{ SalaryStructure::statuses()[$status] }}</flux:badge>
            @endif
        </div>
    </div>

    <flux:separator class="mb-6" />

    <form wire:submit="save" class="space-y-6">
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
            <div>
                <flux:heading size="lg">Structure</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Employee, effective month, basic pay and status.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="employee_id" variant="listbox" searchable label="Employee" placeholder="Pick an employee…" required>
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e['id']" wire:key="emp-{{ $e['id'] }}">{{ $e['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input type="date" wire:model="effective_from" label="Effective From" required />
                </div>
                <flux:error name="employee_id" />
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input type="number" step="0.01" min="0" wire:model.live.debounce.500ms="basic_salary" label="Basic Salary (monthly)" class:input="text-right font-mono" />
                    <flux:select wire:model="status" variant="listbox" label="Status" required>
                        @foreach (SalaryStructure::statuses() as $k => $l)
                            <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator variant="subtle" />

        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
            <div>
                <flux:heading size="lg">Components</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Earnings on top of basic, and deductions. Percent components compute on basic.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex items-center justify-between gap-2">
                    <flux:button type="button" size="sm" variant="ghost" icon="squares-plus" wire:click="addAllComponents">Add all components</flux:button>
                    <flux:dropdown align="end">
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" icon:trailing="chevron-down">Add component</flux:button>
                        <flux:menu>
                            @foreach ($this->components as $c)
                                <flux:menu.item wire:click="addComponent({{ $c->id }})" wire:key="pick-{{ $c->id }}">
                                    {{ $c->name }} <span class="text-xs text-zinc-400 ml-1">{{ ucfirst($c->component_type) }}</span>
                                </flux:menu.item>
                            @endforeach
                        </flux:menu>
                    </flux:dropdown>
                </div>

                @if (count($lines) === 0)
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                        Add earning &amp; deduction components, or click "Add all components".
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach ($lines as $i => $item)
                            @php($amt = ($item['calc_method'] ?? 'fixed') === 'percent_of_basic'
                                ? round(((float) $basic_salary) * (float) ($item['value'] ?? 0) / 100, 2)
                                : (float) ($item['value'] ?? 0))
                            <div wire:key="ss-line-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[110px_1fr_150px_140px_auto] gap-2 items-center rounded-lg border border-zinc-200 dark:border-zinc-800 p-2.5">
                                <flux:badge size="sm" :color="($item['component_type'] ?? 'earning') === 'deduction' ? 'rose' : 'emerald'">{{ ($item['component_type'] ?? 'earning') === 'deduction' ? 'Deduction' : 'Earning' }}</flux:badge>
                                <div class="text-sm font-medium min-w-0 truncate">{{ $item['name'] ?? 'Component' }}</div>
                                <flux:input type="number" step="0.01" min="0" wire:model.live.debounce.500ms="lines.{{ $i }}.value" size="sm"
                                    :label="($item['calc_method'] ?? 'fixed') === 'percent_of_basic' ? 'Value (%)' : 'Amount (₹)'" />
                                <div class="text-right md:pt-4">
                                    <span class="text-xs text-zinc-400">Amount</span>
                                    <span class="font-mono text-sm {{ ($item['component_type'] ?? 'earning') === 'deduction' ? 'text-rose-600 dark:text-rose-400' : '' }}">{{ number_format($amt, 2) }}</span>
                                </div>
                                <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeLine({{ $i }})" />
                            </div>
                        @endforeach
                    </div>
                @endif

                @php($t = $this->totals)
                <div class="mt-2 md:max-w-sm ml-auto rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/30 p-4 space-y-1.5 text-sm">
                    <div class="flex justify-between"><span class="text-zinc-500">Basic</span><span class="font-mono">{{ number_format($t['basic'], 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-zinc-500">Allowances</span><span class="font-mono">+ {{ number_format($t['earnings'], 2) }}</span></div>
                    <div class="flex justify-between font-medium"><span>Gross</span><span class="font-mono">{{ number_format($t['gross'], 2) }}</span></div>
                    <div class="flex justify-between text-rose-600 dark:text-rose-400"><span>Deductions</span><span class="font-mono">− {{ number_format($t['deductions'], 2) }}</span></div>
                    <flux:separator variant="subtle" class="my-1" />
                    <div class="flex justify-between text-base font-semibold"><span>Net Salary</span><span class="font-mono">{{ number_format($t['net'], 2) }}</span></div>
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Optional notes on this salary structure." />
            </div>
        </section>

        <flux:separator />
        <div class="flex items-center justify-end gap-2 py-2">
            <flux:button :href="route('salary-structure.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save Changes' : 'Create Structure' }}</flux:button>
        </div>
    </form>
</div>
