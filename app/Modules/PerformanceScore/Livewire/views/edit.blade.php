@use(App\Modules\PerformanceScore\Models\PerformanceScore)
<div class="max-w-5xl">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:link :href="route('performance-score.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Performance Scores
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? 'Performance Score #'.$editingId : 'New Performance Score' }}</flux:heading>
        </div>
        @php($t = $this->totals)
        <div class="flex items-center gap-2">
            <flux:badge size="lg" :color="$t['percent'] >= 100 ? 'lime' : ($t['percent'] >= 80 ? 'amber' : 'zinc')" class="font-mono">{{ number_format($t['percent'], 1) }}%</flux:badge>
            @if ($editingId)
                <flux:badge :color="$status === 'finalized' ? 'green' : 'sky'" size="lg">{{ PerformanceScore::statuses()[$status] }}</flux:badge>
            @endif
        </div>
    </div>

    <flux:separator class="mb-6" />

    <form wire:submit="save" class="space-y-6">
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
            <div>
                <flux:heading size="lg">Period</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">The employee and the month being scored.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-[1fr_160px_120px] gap-4">
                    <flux:select wire:model="employee_id" variant="listbox" searchable label="Employee" placeholder="Pick an employee…" required>
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e['id']" wire:key="emp-{{ $e['id'] }}">{{ $e['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="period_month" variant="listbox" label="Month" required>
                        @foreach (PerformanceScore::months() as $num => $name)
                            <flux:select.option :value="$num">{{ $name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="period_year" type="number" min="2020" max="2100" label="Year" class:input="font-mono" required />
                </div>
                <flux:error name="employee_id" />
                <flux:select wire:model="status" variant="listbox" label="Status" required class="md:max-w-xs">
                    @foreach (PerformanceScore::statuses() as $k => $l)
                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </section>

        <flux:separator variant="subtle" />

        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
            <div>
                <flux:heading size="lg">KPI Points</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Positive KPIs earn points; negative ones deduct. Achievement % = net ÷ max positive points.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex items-center justify-between gap-2">
                    <flux:button type="button" size="sm" variant="ghost" icon="squares-plus" wire:click="addAllKpis">Add all KPIs</flux:button>
                    <flux:dropdown align="end">
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" icon:trailing="chevron-down">Add KPI</flux:button>
                        <flux:menu class="max-h-80 overflow-y-auto">
                            @foreach ($this->kpis as $k)
                                <flux:menu.item wire:click="addKpi({{ $k->id }})" wire:key="pick-{{ $k->id }}">
                                    {{ $k->name }}
                                    <span class="text-xs ml-1 {{ $k->polarity === 'negative' ? 'text-rose-400' : 'text-emerald-400' }}">{{ $k->polarity === 'negative' ? '−' : '+' }}{{ rtrim(rtrim(number_format($k->weight, 1), '0'), '.') }}</span>
                                </flux:menu.item>
                            @endforeach
                        </flux:menu>
                    </flux:dropdown>
                </div>

                @if (count($lines) === 0)
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                        Add the KPIs this employee is measured on, or click "Add all KPIs".
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach ($lines as $i => $item)
                            <div wire:key="ps-line-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[110px_1fr_120px_120px_auto] gap-2 items-center rounded-lg border border-zinc-200 dark:border-zinc-800 p-2.5">
                                <flux:badge size="sm" :color="($item['polarity'] ?? 'positive') === 'negative' ? 'rose' : 'emerald'">{{ ($item['polarity'] ?? 'positive') === 'negative' ? 'Penalty' : 'Reward' }}</flux:badge>
                                <div class="text-sm font-medium min-w-0 truncate">{{ $item['name'] ?? 'KPI' }}</div>
                                <div class="text-xs text-zinc-500 md:pt-4">Max <span class="font-mono">{{ number_format((float) ($item['max_points'] ?? 0), 1) }}</span></div>
                                <flux:input type="number" step="0.01" min="0" wire:model.live.debounce.500ms="lines.{{ $i }}.points_awarded" size="sm"
                                    :label="($item['polarity'] ?? 'positive') === 'negative' ? 'Penalty pts' : 'Points'" />
                                <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeLine({{ $i }})" />
                            </div>
                        @endforeach
                    </div>
                @endif

                @php($t = $this->totals)
                <div class="mt-2 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/30 p-4 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div><div class="text-zinc-500 text-xs">Positive</div><div class="font-mono font-medium text-emerald-600 dark:text-emerald-400">{{ number_format($t['positive'], 1) }}</div></div>
                    <div><div class="text-zinc-500 text-xs">Negative</div><div class="font-mono font-medium text-rose-600 dark:text-rose-400">− {{ number_format($t['negative'], 1) }}</div></div>
                    <div><div class="text-zinc-500 text-xs">Net / Max</div><div class="font-mono font-medium">{{ number_format($t['net'], 1) }} / {{ number_format($t['max'], 1) }}</div></div>
                    <div><div class="text-zinc-500 text-xs">Achievement</div><div class="font-mono font-semibold">{{ number_format($t['percent'], 1) }}%</div></div>
                </div>

                <div class="rounded-lg border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/50 dark:bg-emerald-900/10 p-4 flex items-center justify-between gap-4">
                    <div>
                        <flux:text size="xs" class="font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Incentive</flux:text>
                        <div class="text-sm text-zinc-600 dark:text-zinc-300 mt-0.5">
                            @if ($t['slab'])Slab <span class="font-medium">{{ $t['slab']->name }}</span> → @endif
                            <span class="font-mono font-semibold text-base">₹ {{ number_format($t['incentive'], 2) }}</span>
                        </div>
                    </div>
                    @if ($editingId)
                        <flux:button type="button" size="sm" variant="ghost" icon="arrow-right-circle" wire:click="pushToPayroll">Push to payroll</flux:button>
                    @endif
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Optional notes on this month's score." />
            </div>
        </section>

        <flux:separator />
        <div class="flex items-center justify-end gap-2 py-2">
            <flux:button :href="route('performance-score.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save Changes' : 'Create Score' }}</flux:button>
        </div>
    </form>
</div>
