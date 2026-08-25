@use(App\Modules\Proforma\Models\Proforma)
<div class="max-w-7xl">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:link :href="route('proforma.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Proformas
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? 'Proforma '.$proforma_no : 'New Proforma' }}</flux:heading>
        </div>
        @if ($editingId)
            @php($t = $this->totals)
            <div class="flex items-center gap-2">
                <flux:badge size="lg" :color="$t['profit'] < 0 ? 'red' : 'lime'" class="font-mono">Profit ₹ {{ number_format($t['profit'], 2) }} ({{ number_format($t['margin'], 1) }}%)</flux:badge>
                <flux:badge :color="match ($status) {
                    'approved' => 'lime', 'rejected' => 'red', 'converted' => 'green', 'cancelled' => 'zinc', default => 'sky',
                }" size="lg">{{ Proforma::statuses()[$status] }}</flux:badge>
            </div>
        @endif
    </div>

    <flux:separator class="mb-6" />

    <form wire:submit="save" novalidate>
        @if (! $editingId)
            @include('proforma::partials.section-details')
            <flux:callout class="mt-6" icon="information-circle" variant="secondary">
                <flux:callout.text>Create the proforma first — line items, deductions and attachments unlock once it exists.</flux:callout.text>
            </flux:callout>
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('proforma.index')" variant="ghost" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Create Proforma</flux:button>
            </div>
        @else
            <flux:tab.group>
                <flux:tabs wire:model="activeTab">
                    <flux:tab name="details" icon="clipboard-document-list">Details</flux:tab>
                    <flux:tab name="lines" icon="list-bullet">Line Items <flux:badge size="sm" class="ml-1">{{ count($items) }}</flux:badge></flux:tab>
                    <flux:tab name="approval" icon="check-badge">Approval &amp; Deductions</flux:tab>
                    <flux:tab name="attachments" icon="paper-clip">Attachments</flux:tab>
                </flux:tabs>

                <flux:tab.panel name="details" class="pt-6">
                    @include('proforma::partials.section-details')
                </flux:tab.panel>

                {{-- LINE ITEMS + PROFITABILITY --}}
                <flux:tab.panel name="lines" class="pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="lg">Line Items</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Cost vs sell per line drives the profitability figures.</flux:text>
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
                                <div wire:key="pf-line-{{ $i }}" class="rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 space-y-2">
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

                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2">
                                        <flux:input type="number" step="0.01" min="0.01" wire:model="items.{{ $i }}.qty" size="sm" label="Qty" />
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
                                        <flux:select wire:model="items.{{ $i }}.rejection_reason_id" variant="listbox" searchable clearable size="sm" label="Issue / Rejection Reason" placeholder="If any…">
                                            @foreach ($this->rejectionReasons as $rr)
                                                <flux:select.option :value="$rr->id" wire:key="rr-{{ $i }}-{{ $rr->id }}">{{ $rr->name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
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
                                <flux:text size="xs" class="font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Profitability</flux:text>
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

                {{-- APPROVAL & DEDUCTIONS --}}
                <flux:tab.panel name="approval" class="pt-6">
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
                        <div>
                            <flux:heading size="lg">Approval &amp; Deductions</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Status, approval, communication, revisions and insurance deductions.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <flux:select wire:model="status" variant="listbox" label="Proforma Status" required>
                                    @foreach (Proforma::statuses() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="approval_status" variant="listbox" label="Approval Status" required>
                                    @foreach (Proforma::approvalStatuses() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="approval_authority" variant="listbox" clearable label="Approval Authority" placeholder="—">
                                    @foreach (Proforma::approvalAuthorities() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <flux:select wire:model="discount_type" variant="listbox" clearable label="Discount Type" placeholder="—">
                                    @foreach (Proforma::discountTypes() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="communication_mode" variant="listbox" clearable label="Communication Mode" placeholder="—">
                                    @foreach (Proforma::communicationModes() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="revision_reason_id" variant="listbox" searchable clearable label="Revision Reason" placeholder="If revised…">
                                    @foreach ($this->revisionReasons as $r)
                                        <flux:select.option :value="$r->id" wire:key="rv-{{ $r->id }}">{{ $r->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <flux:separator variant="subtle" />
                            <div class="flex items-center justify-between">
                                <flux:heading size="sm">Insurance Deductions</flux:heading>
                                <flux:button type="button" size="xs" variant="ghost" icon="plus" wire:click="addDeduction">Add deduction</flux:button>
                            </div>
                            @if (count($deductions) === 0)
                                <flux:text size="sm" class="text-zinc-500">No deductions.</flux:text>
                            @else
                                <div class="space-y-2">
                                    @foreach ($deductions as $i => $ded)
                                        <div wire:key="pf-ded-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[220px_140px_1fr_auto] gap-2 items-end p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                                            <flux:select wire:model="deductions.{{ $i }}.insurance_deduction_type_id" variant="listbox" searchable clearable size="sm" label="Deduction">
                                                @foreach ($this->deductionTypes as $dt)
                                                    <flux:select.option :value="$dt->id" wire:key="dt-{{ $i }}-{{ $dt->id }}">{{ $dt->name }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            <flux:input type="number" step="0.01" min="0" wire:model="deductions.{{ $i }}.amount" size="sm" label="Amount" />
                                            <flux:input wire:model="deductions.{{ $i }}.notes" size="sm" label="Notes" placeholder="Optional" />
                                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeDeduction({{ $i }})" />
                                        </div>
                                    @endforeach
                                    @php($t = $this->totals)
                                    <div class="text-right text-sm font-mono"><span class="text-zinc-500">Total deductions</span> {{ number_format($t['deductions'], 2) }}</div>
                                </div>
                            @endif

                            <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Optional notes for the customer / insurer." />
                        </div>
                    </section>
                </flux:tab.panel>

                {{-- ATTACHMENTS --}}
                <flux:tab.panel name="attachments" class="pt-6">
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
                        <div>
                            <flux:heading size="lg">Attachments</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Inspection report and supporting PDF / images.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <flux:file-upload wire:model="attachmentFiles" multiple accept=".pdf,image/*">
                                <flux:file-upload.dropzone heading="Drop files here or click to browse" text="PDF, JPG, PNG up to 8MB" />
                            </flux:file-upload>
                            @if ($this->existingAttachments->isNotEmpty() || count($attachmentFiles) > 0)
                                <div class="flex flex-col gap-2">
                                    @foreach ($this->existingAttachments as $att)
                                        <flux:file-item wire:key="att-{{ $att->id }}" :heading="$att->original_name ?? 'File #'.$att->id" :size="$att->size_bytes ?? 0">
                                            <x-slot name="actions"><flux:file-item.remove wire:click="removeAttachment({{ $att->id }})" /></x-slot>
                                        </flux:file-item>
                                    @endforeach
                                    @foreach ($attachmentFiles as $i => $file)
                                        <flux:file-item wire:key="att-staged-{{ $i }}" :heading="$file->getClientOriginalName()" :size="$file->getSize()" />
                                    @endforeach
                                </div>
                            @endif
                            <flux:error name="attachmentFiles.*" />
                        </div>
                    </section>
                </flux:tab.panel>
            </flux:tab.group>

            <flux:separator class="mt-4" />
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('proforma.index')" variant="ghost" wire:navigate>Close</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Save Changes</flux:button>
            </div>
        @endif
    </form>
</div>
