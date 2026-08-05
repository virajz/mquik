@use(App\Modules\PurchaseEntry\Models\PurchaseEntry)
<div class="max-w-7xl">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:link :href="route('purchase-entry.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Purchase Entries
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? 'Purchase '.$purchase_no : 'New Purchase Entry' }}</flux:heading>
        </div>
        @if ($editingId)
            @php($t = $this->totals)
            <flux:badge size="lg" color="zinc" class="font-mono">₹ {{ number_format($t['grand'], 2) }}</flux:badge>
        @endif
    </div>

    <flux:separator class="mb-6" />

    <form wire:submit="save">
        @if (! $editingId)
            @include('purchase-entry::partials.section-details')
            <flux:callout class="mt-6" icon="information-circle" variant="secondary">
                <flux:callout.text>Create the purchase first — items, charges and attachments unlock once it exists. Saving received lines updates stock.</flux:callout.text>
            </flux:callout>
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('purchase-entry.index')" variant="ghost" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Create Purchase</flux:button>
            </div>
        @else
            <flux:tab.group>
                <flux:tabs wire:model="activeTab">
                    <flux:tab name="details" icon="clipboard-document-list">Details</flux:tab>
                    <flux:tab name="items" icon="list-bullet">Items <flux:badge size="sm" class="ml-1">{{ count($items) }}</flux:badge></flux:tab>
                    <flux:tab name="charges" icon="banknotes">Charges &amp; Attachments</flux:tab>
                </flux:tabs>

                <flux:tab.panel name="details" class="pt-6">
                    @include('purchase-entry::partials.section-details')
                </flux:tab.panel>

                {{-- ITEMS --}}
                <flux:tab.panel name="items" class="pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="lg">Purchase Items</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Parts received — spare-linked lines post to stock on save.</flux:text>
                        </div>
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addLine">Add item</flux:button>
                    </div>

                    @if (count($items) === 0)
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                            Add the parts on this purchase invoice.
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach ($items as $i => $item)
                                @php($base = (float) ($item['qty'] ?? 0) * (float) ($item['unit_rate'] ?? 0))
                                @php($lt = max(0, $base - (float) ($item['discount_value'] ?? 0)) * (1 + (float) ($item['tax_percent'] ?? 0) / 100))
                                <div wire:key="pe-line-{{ $i }}" class="rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 space-y-2">
                                    <div class="grid grid-cols-1 lg:grid-cols-[1fr_90px_auto] gap-2 items-end">
                                        <flux:select wire:model="items.{{ $i }}.spare_id" variant="listbox" searchable clearable size="sm" label="Spare" placeholder="Pick a spare (free text won't affect stock)…">
                                            @foreach ($this->spares as $s)
                                                <flux:select.option :value="$s->id" wire:key="sp-{{ $i }}-{{ $s->id }}">{{ $s->name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:select wire:model="items.{{ $i }}.uom_id" variant="listbox" clearable size="sm" label="UOM">
                                            @foreach ($this->uoms as $u)
                                                <flux:select.option :value="$u->id" wire:key="um-{{ $i }}-{{ $u->id }}">{{ $u->name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeLine({{ $i }})" />
                                    </div>

                                    <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Description" />

                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2">
                                        <flux:input type="number" step="0.01" min="0.01" wire:model="items.{{ $i }}.qty" size="sm" label="Qty" />
                                        <flux:input type="number" step="0.01" min="0" wire:model="items.{{ $i }}.unit_rate" size="sm" label="Rate" />
                                        <flux:input type="number" step="0.01" min="0" wire:model="items.{{ $i }}.discount_value" size="sm" label="Disc." />
                                        <flux:select wire:model="items.{{ $i }}.tax_id" variant="listbox" clearable size="sm" label="Tax">
                                            @foreach ($this->taxes as $tx)
                                                <flux:select.option :value="$tx->id" wire:key="tx-{{ $i }}-{{ $tx->id }}">{{ $tx->name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:input type="number" step="0.01" min="0" max="100" wire:model="items.{{ $i }}.tax_percent" size="sm" label="Tax %" />
                                        <flux:select wire:model="items.{{ $i }}.material_condition" variant="listbox" size="sm" label="Condition">
                                            @foreach (PurchaseEntry::materialConditions() as $k => $l)
                                                <flux:select.option :value="$k" wire:key="mc-{{ $i }}-{{ $k }}">{{ $l }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>

                                    {{-- Batch & expiry ride onto the stock layer this line creates. --}}
                                    @if (isset($this->batchTrackedSpareIds[$item['spare_id'] ?? 0]))
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                            <flux:input wire:model="items.{{ $i }}.batch_no" size="sm" label="Batch No." placeholder="As printed on the pack" class:input="font-mono" />
                                            <flux:input type="date" wire:model.live="items.{{ $i }}.manufacturing_date" size="sm" label="Mfg. Date" />
                                            <flux:input
                                                type="date"
                                                wire:model="items.{{ $i }}.expiry_date"
                                                size="sm"
                                                label="Expiry Date"
                                                :description="$this->shelfLifeHint($item['spare_id'] ?? null)"
                                            />
                                        </div>
                                    @endif

                                    <div class="grid grid-cols-1 md:grid-cols-[180px_1fr_auto] gap-2 items-end">
                                        <flux:select wire:model="items.{{ $i }}.invoice_status" variant="listbox" size="sm" label="Invoice">
                                            @foreach (PurchaseEntry::invoiceStatuses() as $k => $l)
                                                <flux:select.option :value="$k" wire:key="iv-{{ $i }}-{{ $k }}">{{ $l }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:select wire:model="items.{{ $i }}.rejection_reason_id" variant="listbox" searchable clearable size="sm" label="Dispute / Rejection Reason" placeholder="If any…">
                                            @foreach ($this->rejectionReasons as $rr)
                                                <flux:select.option :value="$rr->id" wire:key="rr-{{ $i }}-{{ $rr->id }}">{{ $rr->name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <div class="text-right text-sm font-mono whitespace-nowrap pb-1"><span class="text-zinc-400 text-xs">Line</span> {{ number_format($lt, 2) }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </flux:tab.panel>

                {{-- CHARGES & ATTACHMENTS --}}
                <flux:tab.panel name="charges" class="pt-6">
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
                        <div>
                            <flux:heading size="lg">Charges &amp; Attachments</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Discount scheme, additional charges, files and the live total.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <flux:select wire:model="discount_scheme" variant="listbox" clearable label="Discount Scheme" placeholder="None" class="max-w-xs">
                                @foreach (PurchaseEntry::discountSchemes() as $k => $l)
                                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                @endforeach
                            </flux:select>

                            <div class="flex items-center justify-between">
                                <flux:heading size="sm">Additional Charges</flux:heading>
                                <flux:button type="button" size="xs" variant="ghost" icon="plus" wire:click="addCharge">Add charge</flux:button>
                            </div>
                            @if (count($charges) === 0)
                                <flux:text size="sm" class="text-zinc-500">No additional charges.</flux:text>
                            @else
                                <div class="space-y-2">
                                    @foreach ($charges as $i => $charge)
                                        <div wire:key="pe-charge-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[200px_140px_1fr_auto] gap-2 items-end p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                                            <flux:select wire:model="charges.{{ $i }}.charge_type_id" variant="listbox" searchable clearable size="sm" label="Charge">
                                                @foreach ($this->chargeTypes as $ct)
                                                    <flux:select.option :value="$ct->id" wire:key="ct-{{ $i }}-{{ $ct->id }}">{{ $ct->name }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            <flux:input type="number" step="0.01" min="0" wire:model="charges.{{ $i }}.amount" size="sm" label="Amount" />
                                            <flux:input wire:model="charges.{{ $i }}.notes" size="sm" label="Notes" placeholder="Optional" />
                                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeCharge({{ $i }})" />
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @php($t = $this->totals)
                            <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/30 p-4 space-y-1.5 text-sm max-w-sm ml-auto">
                                <div class="flex justify-between"><span class="text-zinc-500">Parts</span><span class="font-mono">{{ number_format($t['parts'], 2) }}</span></div>
                                <div class="flex justify-between text-rose-600 dark:text-rose-400"><span>Discount</span><span class="font-mono">− {{ number_format($t['discount'], 2) }}</span></div>
                                <div class="flex justify-between"><span class="text-zinc-500">Tax</span><span class="font-mono">{{ number_format($t['tax'], 2) }}</span></div>
                                <div class="flex justify-between"><span class="text-zinc-500">Charges</span><span class="font-mono">{{ number_format($t['charges'], 2) }}</span></div>
                                <flux:separator variant="subtle" class="my-1" />
                                <div class="flex justify-between text-base font-semibold"><span>Grand Total</span><span class="font-mono">{{ number_format($t['grand'], 2) }}</span></div>
                            </div>

                            <flux:separator variant="subtle" />
                            <flux:heading size="sm">Attachments (PDF / Image)</flux:heading>
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

                            <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Optional notes." />
                        </div>
                    </section>
                </flux:tab.panel>
            </flux:tab.group>

            <flux:separator class="mt-4" />
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('purchase-entry.index')" variant="ghost" wire:navigate>Close</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Save Changes</flux:button>
            </div>
        @endif
    </form>
</div>
