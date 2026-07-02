@use(App\Modules\CounterSalesInvoice\Models\CounterSalesInvoice)
<div class="max-w-7xl">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:link :href="route('counter-sales-invoice.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Counter Sales Invoices
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? 'Counter Invoice '.$invoice_no : 'New Counter Sales Invoice' }}</flux:heading>
        </div>
        @if ($editingId)
            @php($t = $this->totals)
            <div class="flex items-center gap-2">
                <flux:badge size="lg" :color="$t['profit'] < 0 ? 'red' : 'lime'" class="font-mono">Profit ₹ {{ number_format($t['profit'], 2) }} ({{ number_format($t['margin'], 1) }}%)</flux:badge>
                <flux:badge :color="match ($payment_status) {
                    'fully_paid' => 'lime', 'partially_paid' => 'amber', 'refunded' => 'purple', default => 'zinc',
                }" size="lg">{{ CounterSalesInvoice::paymentStatuses()[$payment_status] }}</flux:badge>
                <flux:badge :color="match ($status) {
                    'finalized' => 'green', 'cancelled' => 'zinc', 'credit_note' => 'red', default => 'sky',
                }" size="lg">{{ CounterSalesInvoice::statuses()[$status] }}</flux:badge>
            </div>
        @endif
    </div>

    <flux:separator class="mb-6" />

    <form wire:submit="save">
        @if (! $editingId)
            @include('counter-sales-invoice::partials.section-details')
            <flux:callout class="mt-6" icon="information-circle" variant="secondary">
                <flux:callout.text>Create the invoice first — parts &amp; labour lines unlock once it exists. Finalizing deducts sold spares from stock.</flux:callout.text>
            </flux:callout>
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('counter-sales-invoice.index')" variant="ghost" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Create Invoice</flux:button>
            </div>
        @else
            <flux:tab.group>
                <flux:tabs wire:model="activeTab">
                    <flux:tab name="details" icon="clipboard-document-list">Details</flux:tab>
                    <flux:tab name="lines" icon="list-bullet">Line Items <flux:badge size="sm" class="ml-1">{{ count($items) }}</flux:badge></flux:tab>
                    <flux:tab name="billing" icon="banknotes">Billing &amp; Payment</flux:tab>
                </flux:tabs>

                <flux:tab.panel name="details" class="pt-6">
                    @include('counter-sales-invoice::partials.section-details')
                </flux:tab.panel>

                {{-- LINE ITEMS + MARGIN --}}
                <flux:tab.panel name="lines" class="pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="lg">Line Items</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Cost vs sell per line drives tax and gross margin. Spare lines deduct stock on finalize.</flux:text>
                        </div>
                        <div class="flex gap-2">
                            <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addLine('spare')">Spare</flux:button>
                            <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addLine('labour')">Labour</flux:button>
                        </div>
                    </div>

                    @if (count($items) === 0)
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                            Add spare and labour lines.
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach ($items as $i => $item)
                                @php($sell = (float) ($item['qty'] ?? 0) * (float) ($item['unit_rate'] ?? 0))
                                @php($lt = max(0, $sell - (float) ($item['discount_value'] ?? 0)) * (1 + (float) ($item['tax_percent'] ?? 0) / 100))
                                <div wire:key="csi-line-{{ $i }}" class="rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 space-y-2">
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
                                        <flux:input type="number" step="0.01" min="0" wire:model="items.{{ $i }}.unit_rate" size="sm" label="Sell" />
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
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/30 p-4 space-y-1.5 text-sm">
                                <div class="flex justify-between"><span class="text-zinc-500">Parts</span><span class="font-mono">{{ number_format($t['parts'], 2) }}</span></div>
                                <div class="flex justify-between"><span class="text-zinc-500">Labour</span><span class="font-mono">{{ number_format($t['labour'], 2) }}</span></div>
                                <div class="flex justify-between text-rose-600 dark:text-rose-400"><span>Discount</span><span class="font-mono">− {{ number_format($t['discount'], 2) }}</span></div>
                                <div class="flex justify-between"><span class="text-zinc-500">Tax</span><span class="font-mono">{{ number_format($t['tax'], 2) }}</span></div>
                                <flux:separator variant="subtle" class="my-1" />
                                <div class="flex justify-between text-base font-semibold"><span>Grand Total</span><span class="font-mono">{{ number_format($t['grand'], 2) }}</span></div>
                            </div>
                            <div class="rounded-lg border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/50 dark:bg-emerald-900/10 p-4 space-y-1.5 text-sm">
                                <flux:text size="xs" class="font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Gross Margin</flux:text>
                                <div class="flex justify-between"><span class="text-zinc-500">Cost</span><span class="font-mono">{{ number_format($t['cost'], 2) }}</span></div>
                                <div class="flex justify-between"><span class="text-zinc-500">Net Sell</span><span class="font-mono">{{ number_format($t['parts'] + $t['labour'] - $t['discount'], 2) }}</span></div>
                                <flux:separator variant="subtle" class="my-1" />
                                <div class="flex justify-between text-base font-semibold {{ $t['profit'] < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-700 dark:text-emerald-300' }}">
                                    <span>Profit</span><span class="font-mono">{{ number_format($t['profit'], 2) }} ({{ number_format($t['margin'], 1) }}%)</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </flux:tab.panel>

                {{-- BILLING & PAYMENT --}}
                <flux:tab.panel name="billing" class="pt-6">
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
                        <div>
                            <flux:heading size="lg">Billing &amp; Payment</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Invoice status, payment, discount scheme and cancellation.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <flux:select wire:model.live="status" variant="listbox" label="Invoice Status" required>
                                    @foreach (CounterSalesInvoice::statuses() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="payment_status" variant="listbox" label="Payment Status" required>
                                    @foreach (CounterSalesInvoice::paymentStatuses() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="payment_mode_id" variant="listbox" searchable clearable label="Payment Mode" placeholder="—">
                                    @foreach ($this->paymentModes as $pm)
                                        <flux:select.option :value="$pm->id" wire:key="pm-{{ $pm->id }}">{{ $pm->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <flux:select wire:model="discount_type" variant="listbox" clearable label="Discount Type" placeholder="—">
                                    @foreach (CounterSalesInvoice::discountTypes() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:input type="number" step="0.01" min="0" wire:model.live.debounce.500ms="amount_paid" label="Amount Paid" />
                                <flux:select wire:model="cancellation_reason_id" variant="listbox" searchable clearable label="Cancellation Reason" placeholder="If cancelled / CN…">
                                    @foreach ($this->cancellationReasons as $cr)
                                        <flux:select.option :value="$cr->id" wire:key="cr-{{ $cr->id }}">{{ $cr->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            @php($t = $this->totals)
                            <div class="grid grid-cols-3 gap-3 text-sm">
                                <div class="rounded-md border border-zinc-200 dark:border-zinc-800 p-3"><div class="text-zinc-500 text-xs">Grand Total</div><div class="font-mono font-semibold">{{ number_format($t['grand'], 2) }}</div></div>
                                <div class="rounded-md border border-zinc-200 dark:border-zinc-800 p-3"><div class="text-zinc-500 text-xs">Paid</div><div class="font-mono font-semibold">{{ number_format((float) $amount_paid, 2) }}</div></div>
                                <div class="rounded-md border border-zinc-200 dark:border-zinc-800 p-3"><div class="text-zinc-500 text-xs">Balance Due</div><div class="font-mono font-semibold {{ $t['balance'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ number_format($t['balance'], 2) }}</div></div>
                            </div>

                            <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Optional notes on the counter sale." />
                        </div>
                    </section>
                </flux:tab.panel>
            </flux:tab.group>

            <flux:separator class="mt-4" />
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('counter-sales-invoice.index')" variant="ghost" wire:navigate>Close</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Save Changes</flux:button>
            </div>
        @endif
    </form>
</div>
