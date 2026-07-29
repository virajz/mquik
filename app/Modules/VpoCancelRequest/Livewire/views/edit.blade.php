@php($VCR = \App\Modules\VpoCancelRequest\Models\VpoCancelRequest::class)
@php($ATT = \App\Modules\VpoCancelRequest\Models\VpoCancelRequestAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('vpo-cancel-request.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> VPO Cancel Requests
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($request_no ?: 'Edit Cancel Request') : 'New VPO Cancel Request' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Ask a vendor to cancel a purchase order (full / partial / qty reduction).</flux:text>
        </div>

        <flux:separator />

        {{-- REQUEST --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Request</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">What's being cancelled and why.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="cancellation_request_type" variant="listbox" label="Cancellation Type" required autofocus>
                        @foreach ($VCR::requestTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="cancellation_reason" variant="listbox" clearable label="Cancellation Reason" placeholder="Why cancel…">
                        @foreach ($VCR::cancellationReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="vendor_id" variant="listbox" searchable clearable :filter="false" label="Vendor" placeholder="Supplier…" required>
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Search vendor…" /></x-slot>
                        @foreach ($this->vendors as $v)
                            <flux:select.option :value="$v->id" wire:key="vn-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="vendor_purchase_order_id" variant="listbox" searchable clearable :filter="false" label="Purchase Order" placeholder="PO being cancelled…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="poSearch" placeholder="Search PO…" /></x-slot>
                        @foreach ($this->purchaseOrders as $po)
                            <flux:select.option :value="$po->id" wire:key="po-{{ $po->id }}">{{ $po->po_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="vendor_category" variant="listbox" clearable label="Vendor Category" placeholder="Preferred…">
                        @foreach ($VCR::vendorCategories() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="store_incharge_id" variant="listbox" searchable clearable label="Store In-charge" placeholder="Employee…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="si-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="priority_id" variant="listbox" clearable label="Priority" placeholder="Normal / High / Urgent">
                        @foreach ($this->priorities as $p)<flux:select.option :value="$p->id" wire:key="pr-{{ $p->id }}">{{ $p->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="follow_up_mode_id" variant="listbox" clearable label="Follow-up Mode" placeholder="Call / Email…">
                        @foreach ($this->followUpModes as $fm)<flux:select.option :value="$fm->id" wire:key="fm-{{ $fm->id }}">{{ $fm->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ITEMS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Cancellation Lines</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Each line is a part / job card to cancel, with the qty to cancel.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add line</flux:button>
                </div>

                @foreach ($items as $i => $item)
                    <div wire:key="item-{{ $i }}" class="space-y-2 p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-end">
                            <flux:select wire:model.live="items.{{ $i }}.spare_id" variant="listbox" size="sm" searchable clearable :filter="false" label="Spare" placeholder="Pick from catalogue (optional)…">
                                <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.spareSearch" placeholder="Search spare / part no…" /></x-slot>
                                @foreach ($this->spareOptions($i) as $sp)
                                    <flux:select.option :value="$sp->id" wire:key="sp-{{ $i }}-{{ $sp->id }}">{{ $sp->name }} <span class="text-zinc-400">({{ $sp->spare_code }})</span></flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:button type="button" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" />
                        </div>

                        <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Part description (required)" required />
                        <flux:error name="items.{{ $i }}.description" />

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <flux:select wire:model="items.{{ $i }}.vendor_purchase_order_id" variant="listbox" size="sm" searchable clearable :filter="false" label="PO Ref" placeholder="PO…">
                                <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.poSearch" placeholder="Search PO…" /></x-slot>
                                @foreach ($this->poOptions($i) as $po)
                                    <flux:select.option :value="$po->id" wire:key="ipo-{{ $i }}-{{ $po->id }}">{{ $po->po_no }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.job_card_id" variant="listbox" size="sm" searchable clearable :filter="false" label="Job Card" placeholder="JC…">
                                <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.jobCardSearch" placeholder="Search job card…" /></x-slot>
                                @foreach ($this->jobCardOptions($i) as $jc)
                                    <flux:select.option :value="$jc->id" wire:key="jc-{{ $i }}-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.workshop_department_id" variant="listbox" size="sm" clearable label="Department" placeholder="Dept…">
                                @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="dp-{{ $i }}-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                            </flux:select>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <flux:select wire:model="items.{{ $i }}.advisor_id" variant="listbox" size="sm" searchable clearable label="Advisor" placeholder="Advisor…">
                                @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="ad-{{ $i }}-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.spare_brand_id" variant="listbox" size="sm" searchable clearable label="Brand" placeholder="Brand…">
                                @foreach ($this->spareBrands as $b)<flux:select.option :value="$b->id" wire:key="sb-{{ $i }}-{{ $b->id }}">{{ $b->name }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.uom_id" variant="listbox" size="sm" clearable label="UOM" placeholder="Unit…">
                                @foreach ($this->uoms as $u)<flux:select.option :value="$u->id" wire:key="uom-{{ $i }}-{{ $u->id }}">{{ $u->code ?? $u->name }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.tax_id" variant="listbox" size="sm" clearable label="Tax" placeholder="GST…">
                                @foreach ($this->taxes as $tx)<flux:select.option :value="$tx->id" wire:key="tx-{{ $i }}-{{ $tx->id }}">{{ rtrim(rtrim(number_format((float) $tx->gst_percent, 2), '0'), '.') }}%</flux:select.option>@endforeach
                            </flux:select>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0.01" size="sm" label="Ordered Qty" class:input="text-right font-mono" required />
                            <flux:input wire:model="items.{{ $i }}.quantity_to_cancel" type="number" step="0.01" min="0" size="sm" label="Qty to Cancel" class:input="text-right font-mono" />
                            <flux:input.group label="Rate">
                                <flux:input.group.prefix>₹</flux:input.group.prefix>
                                <flux:input wire:model="items.{{ $i }}.rate" type="number" step="0.01" min="0" size="sm" class:input="text-right font-mono" />
                            </flux:input.group>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <flux:separator />

        {{-- RESPONSE & TERMS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Response & Terms</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Vendor status, charge terms and advance refund.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="status" variant="listbox" label="Cancellation Status" required>
                        @foreach ($VCR::statuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="advance_payment_status" variant="listbox" clearable label="Advance / Refund Status" placeholder="Advance status…">
                        @foreach ($VCR::advancePaymentStatuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end">
                    <flux:select wire:model.live="cancellation_term" variant="listbox" clearable label="Cancellation Term" placeholder="No charge / Fixed / %…">
                        @foreach ($VCR::cancellationTerms() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div x-show="['fixed_charge','percentage_charge'].includes($wire.cancellation_term)" x-cloak>
                        <flux:input.group label="Cancellation Charge">
                            <flux:input.group.prefix>₹/%</flux:input.group.prefix>
                            <flux:input wire:model="cancellation_charge" type="number" step="0.01" min="0" class:input="text-right font-mono" />
                        </flux:input.group>
                        <flux:error name="cancellation_charge" />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="hold_reason" variant="listbox" clearable label="Hold Reason" placeholder="If on hold…">
                        @foreach ($VCR::holdReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="vendor_rating_type" variant="listbox" clearable label="Vendor Rating On" placeholder="Quality / Price…">
                        @foreach ($VCR::vendorRatingTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div x-show="$wire.status === 'vendor_rejected'" x-cloak>
                    <flux:select wire:model="rejection_reason" variant="listbox" clearable label="Rejection Reason (vendor)" placeholder="Why it can't be cancelled…" class="md:max-w-sm">
                        @foreach ($VCR::rejectionReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="rejection_reason" />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ATTACHMENTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Attachments</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Dispatch challan, courier / transport receipt, invoice, refund receipt.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addAttachment">Add file</flux:button>
                </div>

                @forelse ($attachments as $i => $att)
                    <div wire:key="att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2 items-end p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <flux:select wire:model="attachments.{{ $i }}.attachment_type" variant="listbox" size="sm" clearable label="Type" placeholder="Document type…">
                            @foreach ($ATT::attachmentTypes() as $key => $label)
                                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <div>
                            <flux:input type="file" wire:model="attachmentFiles.{{ $i }}" size="sm" label="File" accept=".jpg,.jpeg,.png,.webp,.pdf" />
                            @if (! empty($att['path']))<flux:text size="sm" class="text-zinc-500 mt-1">Current: {{ $att['original_name'] ?? basename($att['path']) }}</flux:text>@endif
                            <flux:error name="attachmentFiles.{{ $i }}" />
                        </div>
                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeAttachment({{ $i }})" class="h-9!" />
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">No files yet.</div>
                @endforelse

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Anything the vendor should know." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('vpo-cancel-request.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create request' }}</flux:button>
        </div>
    </form>
</div>
