@use(App\Modules\SalesEstimate\Models\SalesEstimate)
<div class="max-w-7xl">
    {{-- HEADER --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:link :href="route('sales-estimate.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Sales Estimates
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? 'Estimate '.$estimate_no : 'New Sales Estimate' }}</flux:heading>
            @if ($old_estimate_id)
                <flux:text size="sm" class="mt-1 text-zinc-500">Revision of estimate #{{ $old_estimate_id }}</flux:text>
            @endif
        </div>
        @if ($editingId)
            <div class="flex items-center gap-2">
                @can('sales_estimate.create')
                    <flux:button type="button" size="sm" variant="ghost" icon="arrow-path" wire:click="revise"
                        wire:confirm="Create a new revision from this estimate? The current one will be marked Revised.">Revise</flux:button>
                @endcan
                <flux:badge :color="match ($status) {
                    'approved' => 'lime', 'partially_approved' => 'teal', 'rejected' => 'red',
                    'converted' => 'green', 'revised' => 'orange', 'under_approval' => 'amber', default => 'zinc',
                }" size="lg">{{ SalesEstimate::statuses()[$status] }}</flux:badge>
            </div>
        @endif
    </div>

    <flux:separator class="mb-6" />

    <form wire:submit="save">
        @if (! $editingId)
            {{-- LEAN CREATE --}}
            @include('sales-estimate::partials.section-details')
            <flux:callout class="mt-6" icon="information-circle" variant="secondary">
                <flux:callout.text>Create the estimate first — line items, discounts and totals unlock once it exists.</flux:callout.text>
            </flux:callout>
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('sales-estimate.index')" variant="ghost" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Create Estimate</flux:button>
            </div>
        @else
            {{-- RICH EDIT --}}
            <flux:tab.group>
                <flux:tabs wire:model="activeTab">
                    <flux:tab name="details" icon="clipboard-document-list">Details</flux:tab>
                    <flux:tab name="lines" icon="list-bullet">Line Items <flux:badge size="sm" class="ml-1">{{ count($items) }}</flux:badge></flux:tab>
                    <flux:tab name="totals" icon="calculator">Totals &amp; Status</flux:tab>
                </flux:tabs>

                <flux:tab.panel name="details" class="pt-6">
                    @include('sales-estimate::partials.section-details')
                </flux:tab.panel>

                {{-- LINE ITEMS --}}
                <flux:tab.panel name="lines" class="pt-6">
                    <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
                        <div>
                            <flux:heading size="lg">Line Items</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Spares &amp; labour with rate, tax and insurance-approval flag.</flux:text>
                        </div>
                        <div class="flex items-end gap-2">
                            <flux:select wire:model="estimate_template_id" variant="listbox" searchable clearable size="sm" placeholder="Apply template…" class="w-48">
                                @foreach ($this->templates as $t)
                                    <flux:select.option :value="$t->id" wire:key="tpl-{{ $t->id }}">{{ $t->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="applyTemplate">Apply</flux:button>
                            <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addLine('spare')">Spare</flux:button>
                            <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addLine('labour')">Labour</flux:button>
                        </div>
                    </div>

                    @if (count($items) === 0)
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                            Add spare or labour lines, or apply a template.
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach ($items as $i => $item)
                                @php($base = (float) ($item['qty'] ?? 0) * (float) ($item['unit_rate'] ?? 0))
                                @php($lineTotal = $base * (1 + (float) ($item['tax_percent'] ?? 0) / 100))
                                <div wire:key="line-{{ $i }}" class="rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 space-y-2">
                                    <div class="grid grid-cols-1 lg:grid-cols-[90px_1fr_90px_120px_110px_auto] gap-2 items-end">
                                        <flux:badge size="sm" :color="$item['line_type'] === 'labour' ? 'purple' : 'sky'" class="mb-2">{{ ucfirst($item['line_type']) }}</flux:badge>

                                        @if ($item['line_type'] === 'spare')
                                            <flux:select wire:model="items.{{ $i }}.spare_id" variant="listbox" searchable size="sm" label="Spare" placeholder="Pick a spare…">
                                                @foreach ($this->spares as $s)
                                                    <flux:select.option :value="$s->id" wire:key="sp-{{ $i }}-{{ $s->id }}">{{ $s->name }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        @else
                                            <flux:select wire:model="items.{{ $i }}.labour_id" variant="listbox" searchable size="sm" label="Labour" placeholder="Pick a labour…">
                                                @foreach ($this->labours as $l)
                                                    <flux:select.option :value="$l->id" wire:key="lb-{{ $i }}-{{ $l->id }}">{{ $l->name }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        @endif

                                        <flux:input type="number" step="0.01" min="0.01" wire:model="items.{{ $i }}.qty" size="sm" label="Qty" />
                                        <flux:input type="number" step="0.01" min="0" wire:model="items.{{ $i }}.unit_rate" size="sm" label="Rate" />
                                        <flux:select wire:model="items.{{ $i }}.tax_id" variant="listbox" size="sm" clearable label="Tax">
                                            @foreach ($this->taxes as $t)
                                                <flux:select.option :value="$t->id" wire:key="tx-{{ $i }}-{{ $t->id }}">{{ $t->name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeLine({{ $i }})" />
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_auto] gap-3 items-center">
                                        <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Description" />
                                        <flux:checkbox wire:model="items.{{ $i }}.is_insurance_approved" label="Insurance approved" />
                                        <div class="text-right text-sm font-mono whitespace-nowrap">
                                            <span class="text-zinc-400 text-xs">Line</span> {{ number_format($lineTotal, 2) }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </flux:tab.panel>

                {{-- TOTALS & STATUS --}}
                <flux:tab.panel name="totals" class="pt-6">
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
                        <div>
                            <flux:heading size="lg">Pricing &amp; Status</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Discount, labour tier, workflow status and the live totals.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <flux:select wire:model.live="discount_type" variant="listbox" clearable label="Discount Type" placeholder="No discount">
                                    @foreach (SalesEstimate::discountTypes() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:input type="number" step="0.01" min="0" wire:model.live="discount_value" label="Discount Value" />
                                <flux:select wire:model="labour_price_tier" variant="listbox" label="Labour Price Tier" required>
                                    @foreach (SalesEstimate::labourPriceTiers() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:select wire:model="status" variant="listbox" label="Status" required>
                                    @foreach (SalesEstimate::statuses() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="revision_reason_id" variant="listbox" searchable clearable label="Revision Reason" placeholder="If revised…">
                                    @foreach ($this->revisionReasons as $r)
                                        <flux:select.option :value="$r->id" wire:key="rr-{{ $r->id }}">{{ $r->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Optional notes for the customer / insurer." />

                            {{-- LIVE TOTALS --}}
                            @php($t = $this->totals)
                            <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/30 p-4 space-y-1.5 text-sm">
                                <div class="flex justify-between"><span class="text-zinc-500">Parts</span><span class="font-mono">{{ number_format($t['parts'], 2) }}</span></div>
                                <div class="flex justify-between"><span class="text-zinc-500">Labour</span><span class="font-mono">{{ number_format($t['labour'], 2) }}</span></div>
                                <div class="flex justify-between"><span class="text-zinc-500">Subtotal</span><span class="font-mono">{{ number_format($t['subtotal'], 2) }}</span></div>
                                <div class="flex justify-between text-rose-600 dark:text-rose-400"><span>Discount</span><span class="font-mono">− {{ number_format($t['discount'], 2) }}</span></div>
                                <div class="flex justify-between"><span class="text-zinc-500">Tax</span><span class="font-mono">{{ number_format($t['tax'], 2) }}</span></div>
                                <flux:separator variant="subtle" class="my-1" />
                                <div class="flex justify-between text-base font-semibold"><span>Grand Total</span><span class="font-mono">{{ number_format($t['grand'], 2) }}</span></div>
                                <div class="flex justify-between pt-1"><span class="text-zinc-500">Insurance Pass %</span><flux:badge size="sm" color="sky">{{ number_format($t['pass'], 2) }}%</flux:badge></div>
                            </div>
                        </div>
                    </section>
                </flux:tab.panel>
            </flux:tab.group>

            <flux:separator class="mt-4" />
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('sales-estimate.index')" variant="ghost" wire:navigate>Close</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Save Changes</flux:button>
            </div>
        @endif
    </form>
</div>
