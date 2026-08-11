@php($VPO = \App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrder::class)
@php($ITEM = \App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrderItem::class)
@php($ATT = \App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrderAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('vendor-purchase-order.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Vendor Purchase Orders
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($po_no ?: 'Edit PO') : 'New Vendor Purchase Order' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">The confirmed order placed on a vendor after inquiry/approval.</flux:text>
            @if ($sourceVpiNo)
                <flux:badge color="sky" size="sm" icon="arrow-right-circle" class="mt-2">Carried forward from {{ $sourceVpiNo }}</flux:badge>
            @endif
            @if ($sourceIpoNo)
                <flux:badge color="amber" size="sm" icon="arrow-right-circle" class="mt-2">Escalated from {{ $sourceIpoNo }}</flux:badge>
            @endif
        </div>

        <flux:separator />

        {{-- ORDER --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Order</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Type, vendor and source references.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="po_type" variant="listbox" label="PO Type" required autofocus>
                        @foreach ($VPO::poTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="priority_id" variant="listbox" clearable label="Priority" placeholder="Normal / High / Urgent">
                        @foreach ($this->priorities as $p)
                            <flux:select.option :value="$p->id" wire:key="pr-{{ $p->id }}">{{ $p->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="vendor_id" variant="listbox" searchable clearable :filter="false" label="Supplier / Vendor" placeholder="Who is being ordered from…" required>
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Search vendor…" /></x-slot>
                        @foreach ($this->vendors as $v)
                            <flux:select.option :value="$v->id" wire:key="vn-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="vendor_type_id" variant="listbox" clearable label="Vendor Type" placeholder="Dealer / Distributor…">
                        @foreach ($this->vendorTypes as $vt)
                            <flux:select.option :value="$vt->id" wire:key="vt-{{ $vt->id }}">{{ $vt->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="Link a job card…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                        @foreach ($this->jobCards as $jc)
                            <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="vendor_purchase_inquiry_id" variant="listbox" searchable clearable :filter="false" label="From VPI / RFQ" placeholder="Source inquiry…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="inquirySearch" placeholder="Search VPI…" /></x-slot>
                        @foreach ($this->inquiries as $iq)
                            <flux:select.option :value="$iq->id" wire:key="iq-{{ $iq->id }}">{{ $iq->vpi_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="vpo_approval_id" variant="listbox" clearable label="PO Approval Ref" placeholder="Approved via…">
                        @foreach ($this->approvals as $ap)
                            <flux:select.option :value="$ap->id" wire:key="ap-{{ $ap->id }}">{{ $ap->approval_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="advance_payment_id" variant="listbox" clearable label="Advance Payment Ref" placeholder="If advance paid…">
                        @foreach ($this->advancePayments as $ap)
                            <flux:select.option :value="$ap->id" wire:key="adp-{{ $ap->id }}">{{ $ap->payment_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="employee_id" variant="listbox" searchable clearable label="Raised By" placeholder="Store / advisor…">
                        @foreach ($this->employees as $e)
                            <flux:select.option :value="$e->id" wire:key="em-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="vendor_category" variant="listbox" clearable label="Vendor Category" placeholder="Preferred…">
                        @foreach ($VPO::vendorCategories() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="vendor_rating_type" variant="listbox" clearable label="Rating On" placeholder="Quality / Price…">
                        @foreach ($VPO::vendorRatingTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ORDERED PARTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Ordered Parts</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Each line is an ordered part — agreed rate, discount, warranty, tax.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add part</flux:button>
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

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <flux:select wire:model="items.{{ $i }}.spare_brand_id" variant="listbox" size="sm" searchable clearable label="Brand" placeholder="Brand…">
                                @foreach ($this->spareBrands as $b)
                                    <flux:select.option :value="$b->id" wire:key="sb-{{ $i }}-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.part_type_id" variant="listbox" size="sm" clearable label="Inventory Type" placeholder="Genuine / Aftermarket…">
                                @foreach ($this->partTypes as $pt)
                                    <flux:select.option :value="$pt->id" wire:key="pt-{{ $i }}-{{ $pt->id }}">{{ $pt->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.uom_id" variant="listbox" size="sm" clearable label="UOM" placeholder="Unit…">
                                @foreach ($this->uoms as $u)
                                    <flux:select.option :value="$u->id" wire:key="uom-{{ $i }}-{{ $u->id }}">{{ $u->code ?? $u->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.tax_id" variant="listbox" size="sm" clearable label="Tax" placeholder="GST…">
                                @foreach ($this->taxes as $tx)
                                    <flux:select.option :value="$tx->id" wire:key="tx-{{ $i }}-{{ $tx->id }}">{{ rtrim(rtrim(number_format((float) $tx->gst_percent, 2), '0'), '.') }}%</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0.01" size="sm" label="Qty" class:input="text-right font-mono" required />
                            <flux:input.group label="Rate">
                                <flux:input.group.prefix>₹</flux:input.group.prefix>
                                <flux:input wire:model="items.{{ $i }}.rate" type="number" step="0.01" min="0" size="sm" class:input="text-right font-mono" />
                            </flux:input.group>
                            <flux:select wire:model="items.{{ $i }}.discount_type" variant="listbox" size="sm" clearable label="Discount" placeholder="Type…">
                                @foreach ($ITEM::discountTypes() as $key => $label)
                                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:input wire:model="items.{{ $i }}.discount_value" type="number" step="0.01" min="0" size="sm" label="Disc. Value" class:input="text-right font-mono" />
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <flux:select wire:model="items.{{ $i }}.warranty_type" variant="listbox" size="sm" clearable label="Warranty" placeholder="Type…">
                                @foreach ($ITEM::warrantyTypes() as $key => $label)
                                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:input.group label="Warranty Period">
                                <flux:input wire:model="items.{{ $i }}.warranty_period_value" type="number" min="1" max="999" size="sm" class:input="text-right font-mono" />
                                <flux:select wire:model="items.{{ $i }}.warranty_period_unit" size="sm">
                                    @foreach ($ITEM::warrantyUnits() as $key => $label)
                                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </flux:input.group>
                            <flux:input wire:model="items.{{ $i }}.lead_time_days" type="number" min="0" max="999" size="sm" label="Lead Time (days)" class:input="text-right font-mono" />
                            <flux:input wire:model="items.{{ $i }}.closing_stock" type="number" step="0.01" min="0" size="sm" label="Closing Stock" class:input="text-right font-mono" />
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <flux:separator />

        {{-- ADDITIONAL CHARGES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Additional Charges</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Freight, P&F, transport, packing, handling, etc.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addCharge">Add charge</flux:button>
                </div>

                @forelse ($charges as $i => $charge)
                    <div wire:key="charge-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_180px_auto] gap-2 items-end p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <flux:select wire:model="charges.{{ $i }}.charge_type_id" variant="listbox" size="sm" searchable clearable label="Charge" placeholder="Charge head…">
                            @foreach ($this->chargeTypes as $ct)
                                <flux:select.option :value="$ct->id" wire:key="ct-{{ $i }}-{{ $ct->id }}">{{ $ct->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input.group label="Amount">
                            <flux:input.group.prefix>₹</flux:input.group.prefix>
                            <flux:input wire:model="charges.{{ $i }}.amount" type="number" step="0.01" min="0" size="sm" class:input="text-right font-mono" />
                        </flux:input.group>
                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeCharge({{ $i }})" class="h-9!" />
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                        No extra charges. Click <span class="font-medium">Add charge</span> if the order includes freight, packing, etc.
                    </div>
                @endforelse
            </div>
        </section>

        <flux:separator />

        {{-- DELIVERY --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Delivery & Terms</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Payment, delivery mode, commitment and logistics.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="payment_term" variant="listbox" clearable label="Payment Terms" placeholder="Advance / COD / Credit…">
                        @foreach ($VPO::paymentTerms() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="delivery_mode" variant="listbox" clearable label="Delivery Mode" placeholder="Pickup / Courier…">
                        @foreach ($VPO::deliveryModes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="transport_company_id" variant="listbox" searchable clearable :filter="false" label="Transport / Logistics" placeholder="Logistics vendor…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="transportSearch" placeholder="Search vendor…" /></x-slot>
                        @foreach ($this->transportCompanies as $t)
                            <flux:select.option :value="$t->id" wire:key="tp-{{ $t->id }}">{{ $t->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <flux:select wire:model.live="delivery_commitment" variant="listbox" clearable label="Delivery Commitment" placeholder="Expected…">
                        @foreach ($VPO::deliveryCommitments() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div x-show="$wire.delivery_commitment === 'custom'" x-cloak>
                        <flux:input wire:model="delivery_custom_days" type="number" min="1" max="365" label="Custom (days)" class:input="font-mono" />
                        <flux:error name="delivery_custom_days" />
                    </div>
                    <flux:date-picker wire:model="expected_delivery_date" label="Expected Delivery Date" with-today selectable-header fixed-weeks type="input" />
                </div>
                <flux:textarea wire:model="terms_conditions" label="T&Cs / SLA" placeholder="Delivery commitments, penalty rules, service-level terms…" rows="2" />
            </div>
        </section>

        <flux:separator />

        {{-- VENDOR RESPONSE (VPR) --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Vendor Response</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Acknowledgement and dispatch details (VPR).</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="acknowledgement_status" variant="listbox" label="Acknowledgement" required>
                        @foreach ($VPO::acknowledgementStatuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div x-show="$wire.acknowledgement_status === 'rejected'" x-cloak>
                        <flux:select wire:model="rejection_reason" variant="listbox" clearable label="Rejection Reason" placeholder="Why rejected">
                            @foreach ($VPO::rejectionReasons() as $key => $label)
                                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="rejection_reason" />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input wire:model="courier_company" label="Courier Company" placeholder="Bluedart / DTDC…" />
                    <flux:input wire:model="consignment_no" label="Consignment No" placeholder="AWB / LR no" class:input="font-mono" />
                    <flux:date-picker wire:model="consignment_date" label="Consignment Date" with-today selectable-header fixed-weeks type="input" />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ATTACHMENTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Attachments</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">PO copy, dispatch copy, invoice copy, photo evidence — PDF or image.</flux:text>
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
                            @if (! empty($att['path']))
                                <flux:text size="sm" class="text-zinc-500 mt-1">Current: {{ $att['original_name'] ?? basename($att['path']) }}</flux:text>
                            @endif
                            <flux:error name="attachmentFiles.{{ $i }}" />
                        </div>
                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeAttachment({{ $i }})" class="h-9!" />
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                        No files yet. Click <span class="font-medium">Add file</span> to attach the PO or dispatch copy.
                    </div>
                @endforelse
            </div>
        </section>

        <flux:separator />

        {{-- STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Status</flux:heading>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="status" variant="listbox" label="PO Status" required>
                        @foreach ($VPO::statuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div x-show="$wire.status === 'cancelled'" x-cloak>
                        <flux:select wire:model="cancellation_reason_id" variant="listbox" clearable label="Cancellation Reason" placeholder="Why cancelled…">
                            @foreach ($this->cancellationReasons as $cr)
                                <flux:select.option :value="$cr->id" wire:key="cr-{{ $cr->id }}">{{ $cr->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
                <div x-show="$wire.status === 'cancelled'" x-cloak>
                    <flux:input wire:model="cancellation_note" label="Cancellation Note" placeholder="Optional detail" />
                </div>
                <flux:textarea wire:model="notes" label="Notes" placeholder="Anything the store/vendor should know." rows="2" />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('vendor-purchase-order.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create PO' }}</flux:button>
        </div>
    </form>
</div>
