@use(App\Modules\SalesReturn\Models\SalesReturn)
<div class="max-w-7xl">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:link :href="route('sales-return.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Sales Returns
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? 'Sales Return '.$return_no : 'New Sales Return' }}</flux:heading>
        </div>
        @if ($editingId)
            @php($t = $this->totals)
            <div class="flex items-center gap-2">
                <flux:badge size="lg" color="zinc">{{ SalesReturn::returnTypes()[$return_type] }}</flux:badge>
                <flux:badge size="lg" class="font-mono">₹ {{ number_format($t['grand'], 2) }}</flux:badge>
                <flux:badge :color="match ($refund_status) {
                    'fully_refunded' => 'lime', 'partially_refunded' => 'amber', default => 'zinc',
                }" size="lg">{{ SalesReturn::refundStatuses()[$refund_status] }}</flux:badge>
                <flux:badge :color="match ($status) {
                    'finalized' => 'green', 'cancelled' => 'red', default => 'sky',
                }" size="lg">{{ SalesReturn::statuses()[$status] }}</flux:badge>
            </div>
        @endif
    </div>

    <flux:separator class="mb-6" />

    <form wire:submit="save" novalidate>
        @if (! $editingId)
            @include('sales-return::partials.section-details')
            <flux:callout class="mt-6" icon="information-circle" variant="secondary">
                <flux:callout.text>Create the return first — line items unlock once it exists. Finalizing restores returned spares to stock. Tip: open from an invoice to snapshot its lines.</flux:callout.text>
            </flux:callout>
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('sales-return.index')" variant="ghost" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Create Return</flux:button>
            </div>
        @else
            <flux:tab.group>
                <flux:tabs wire:model="activeTab">
                    <flux:tab name="details" icon="clipboard-document-list">Details</flux:tab>
                    <flux:tab name="lines" icon="list-bullet">Return Items <flux:badge size="sm" class="ml-1">{{ count($items) }}</flux:badge></flux:tab>
                    <flux:tab name="refund" icon="banknotes">Refund</flux:tab>
                </flux:tabs>

                <flux:tab.panel name="details" class="pt-6">
                    @include('sales-return::partials.section-details')
                </flux:tab.panel>

                {{-- RETURN ITEMS --}}
                <flux:tab.panel name="lines" class="pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="lg">Return Items</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Quantities being returned. Spare lines restore stock on finalize.</flux:text>
                        </div>
                        <div class="flex gap-2">
                            <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addLine('spare')">Spare</flux:button>
                            <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addLine('labour')">Labour</flux:button>
                        </div>
                    </div>

                    @if (count($items) === 0)
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                            Add the spare / labour lines being returned, or open the return from an invoice to pull its lines.
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach ($items as $i => $item)
                                @php($sell = (float) ($item['qty'] ?? 0) * (float) ($item['unit_rate'] ?? 0))
                                @php($lt = max(0, $sell - (float) ($item['discount_value'] ?? 0)) * (1 + (float) ($item['tax_percent'] ?? 0) / 100))
                                <div wire:key="sr-line-{{ $i }}" class="rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 space-y-2">
                                    <div class="grid grid-cols-1 lg:grid-cols-[90px_1fr_auto] gap-2 items-end">
                                        <flux:badge size="sm" :color="$item['line_type'] === 'labour' ? 'purple' : 'sky'" class="mb-2">{{ ucfirst($item['line_type']) }}</flux:badge>
                                        @if ($item['line_type'] === 'spare')
                                            <flux:select wire:model="items.{{ $i }}.spare_id" variant="listbox" searchable clearable size="sm" label="Spare" placeholder="Pick…">
                                                @foreach ($this->spares as $s)
                                                    <flux:select.option :value="$s->id" wire:key="sp-{{ $i }}-{{ $s->id }}">{{ $s->name }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        @else
                                            <flux:select wire:model="items.{{ $i }}.labour_id" variant="listbox" searchable clearable size="sm" label="Labour" placeholder="Pick…">
                                                @foreach ($this->labours as $l)
                                                    <flux:select.option :value="$l->id" wire:key="lb-{{ $i }}-{{ $l->id }}">{{ $l->name }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        @endif
                                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeLine({{ $i }})" />
                                    </div>

                                    <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Description" />

                                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-2">
                                        <flux:input type="number" step="0.01" min="0.01" wire:model="items.{{ $i }}.qty" size="sm" label="Qty" />
                                        <flux:select wire:model="items.{{ $i }}.uom_id" variant="listbox" clearable size="sm" label="UOM">
                                            @foreach ($this->uoms as $u)
                                                <flux:select.option :value="$u->id" wire:key="uom-{{ $i }}-{{ $u->id }}">{{ $u->name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:input type="number" step="0.01" min="0" wire:model="items.{{ $i }}.cost_rate" size="sm" label="Cost" />
                                        <flux:input type="number" step="0.01" min="0" wire:model="items.{{ $i }}.unit_rate" size="sm" label="Rate" />
                                        <flux:input type="number" step="0.01" min="0" wire:model="items.{{ $i }}.discount_value" size="sm" label="Disc." />
                                        <flux:select wire:model="items.{{ $i }}.tax_id" variant="listbox" clearable size="sm" label="Tax">
                                            @foreach ($this->taxes as $tx)
                                                <flux:select.option :value="$tx->id" wire:key="tx-{{ $i }}-{{ $tx->id }}">{{ $tx->name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:input type="number" step="0.01" min="0" max="100" wire:model="items.{{ $i }}.tax_percent" size="sm" label="Tax %" />
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-center">
                                        <flux:input wire:model="items.{{ $i }}.hsn_code" size="sm" label="HSN / SAC" class:input="font-mono" />
                                        <div class="text-right text-sm font-mono whitespace-nowrap pb-1"><span class="text-zinc-400 text-xs">Line</span> {{ number_format($lt, 2) }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @php($t = $this->totals)
                        <div class="mt-4 md:max-w-sm ml-auto rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/30 p-4 space-y-1.5 text-sm">
                            <div class="flex justify-between"><span class="text-zinc-500">Parts</span><span class="font-mono">{{ number_format($t['parts'], 2) }}</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">Labour</span><span class="font-mono">{{ number_format($t['labour'], 2) }}</span></div>
                            <div class="flex justify-between text-rose-600 dark:text-rose-400"><span>Discount</span><span class="font-mono">− {{ number_format($t['discount'], 2) }}</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">Tax</span><span class="font-mono">{{ number_format($t['tax'], 2) }}</span></div>
                            <flux:separator variant="subtle" class="my-1" />
                            <div class="flex justify-between text-base font-semibold"><span>Return Value</span><span class="font-mono">{{ number_format($t['grand'], 2) }}</span></div>
                        </div>
                    @endif
                </flux:tab.panel>

                {{-- REFUND --}}
                <flux:tab.panel name="refund" class="pt-6">
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
                        <div>
                            <flux:heading size="lg">Refund &amp; Status</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Return status, refund tracking, discount scheme and cancellation.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <flux:select wire:model="status" variant="listbox" label="Return Status" required>
                                    @foreach (SalesReturn::statuses() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="refund_status" variant="listbox" label="Refund Status" required>
                                    @foreach (SalesReturn::refundStatuses() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:input type="number" step="0.01" min="0" wire:model.live.debounce.500ms="refunded_amount" label="Refunded Amount" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:select wire:model="discount_type" variant="listbox" clearable label="Discount Type" placeholder="—">
                                    @foreach (SalesReturn::discountTypes() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="cancellation_reason_id" variant="listbox" searchable clearable label="Return Cancellation Reason" placeholder="If cancelled…">
                                    @foreach ($this->cancellationReasons as $cr)
                                        <flux:select.option :value="$cr->id" wire:key="cr-{{ $cr->id }}">{{ $cr->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            @php($t = $this->totals)
                            <div class="grid grid-cols-3 gap-3 text-sm">
                                <div class="rounded-md border border-zinc-200 dark:border-zinc-800 p-3"><div class="text-zinc-500 text-xs">Return Value</div><div class="font-mono font-semibold">{{ number_format($t['grand'], 2) }}</div></div>
                                <div class="rounded-md border border-zinc-200 dark:border-zinc-800 p-3"><div class="text-zinc-500 text-xs">Refunded</div><div class="font-mono font-semibold">{{ number_format((float) $refunded_amount, 2) }}</div></div>
                                <div class="rounded-md border border-zinc-200 dark:border-zinc-800 p-3"><div class="text-zinc-500 text-xs">Balance to Refund</div><div class="font-mono font-semibold {{ $t['balance'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ number_format($t['balance'], 2) }}</div></div>
                            </div>

                            <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Optional notes on the return." />
                        </div>
                    </section>
                </flux:tab.panel>
            </flux:tab.group>

            <flux:separator class="mt-4" />
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('sales-return.index')" variant="ghost" wire:navigate>Close</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Save Changes</flux:button>
            </div>
        @endif
    </form>
</div>
