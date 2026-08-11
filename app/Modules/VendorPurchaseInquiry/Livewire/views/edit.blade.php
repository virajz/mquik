@php($VPI = \App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry::class)
@php($ITEM = \App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiryItem::class)
@php($ATT = \App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiryAttachment::class)
@php($VENDOR = \App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiryVendor::class)
@php($QUOTE = \App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiryQuote::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        {{-- A disabled fieldset turns off every control inside it natively, so a
             read-only viewer cannot edit anything without duplicating the form. --}}
        <fieldset @disabled(! $this->canEdit) class="min-w-0">
        @unless ($this->canEdit)
            <flux:callout variant="secondary" icon="eye" heading="View only" class="mb-6">
                You can see the quotes and prices on this inquiry. Changing it is the store's job.
            </flux:callout>
        @endunless
        <div class="mb-8 flex items-start justify-between gap-4">
            <div>
                <flux:link :href="route('vendor-purchase-inquiry.index')" variant="ghost" class="text-xs">
                    <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Vendor Purchase Inquiries
                </flux:link>
                <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($vpi_no ?: 'Edit RFQ') : 'New Vendor Purchase Inquiry (RFQ)' }}</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Ask a vendor for part rate, brand, delivery time, warranty and payment terms.</flux:text>
                @if ($sourceIpiNo)
                    <flux:badge color="sky" size="sm" icon="arrow-right-circle" class="mt-2">Carried forward from {{ $sourceIpiNo }}</flux:badge>
                @endif
            </div>
            @if ($editingId)
                @can('vendor_purchase_order.create')
                    <flux:tooltip :content="$this->isCustomerApproved ? 'Create the order from the chosen vendor' : 'Record the customer approval first'">
                        <flux:button :href="route('vendor-purchase-order.create', ['from-vpi' => $editingId])" wire:navigate
                            size="sm" :variant="$this->isCustomerApproved ? 'primary' : 'ghost'" icon="arrow-right-circle">
                            Raise Purchase Order
                        </flux:button>
                    </flux:tooltip>
                @endcan
            @endif
        </div>

        <flux:separator />

        {{-- INQUIRY --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Inquiry</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Type, vendor and who's raising it.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="inquiry_type" variant="listbox" label="Inquiry Type" required autofocus>
                        @foreach ($VPI::inquiryTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="priority_id" variant="listbox" clearable label="Priority" placeholder="Normal / High / Urgent">
                        @foreach ($this->priorities as $p)
                            <flux:select.option :value="$p->id" wire:key="pr-{{ $p->id }}">{{ $p->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="vendor_id" variant="listbox" searchable clearable :filter="false" label="Supplier / Vendor" placeholder="Who is being asked…" required>
                        <x-slot name="search">
                            <flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Search vendor…" />
                        </x-slot>
                        @foreach ($this->vendors as $v)
                            <flux:select.option :value="$v->id" wire:key="vn-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="Link a job card…">
                        <x-slot name="search">
                            <flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" />
                        </x-slot>
                        @foreach ($this->jobCards as $jc)
                            <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
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
                        @foreach ($VPI::vendorCategories() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="vendor_rating_type" variant="listbox" clearable label="Rating On" placeholder="Quality / Price…">
                        @foreach ($VPI::vendorRatingTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- VENDORS THIS RFQ GOES TO — procurement only. An advisor cares what a
             part costs and what grade it is, not who supplies it. --}}
        @if ($this->showsVendors)
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Vendors</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">
                    Send the same inquiry to several suppliers and compare what comes back. Tick the one you order from.
                </flux:text>
                @if ($editingId)
                    <flux:modal.trigger name="vpi-dispatch">
                        <flux:button type="button" size="sm" variant="ghost" icon="paper-airplane" class="mt-3">
                            Message to send
                        </flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
            <div class="space-y-3 min-w-0">
                {{-- The customer's yes, before any money is committed. --}}
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-0 flex-1">
                        <flux:select wire:model.live="customer_approval_id" variant="listbox" clearable size="sm"
                            label="Customer Approval" placeholder="Not approved yet…">
                            @foreach ($this->customerApprovals as $ap)
                                <flux:select.option :value="$ap->id" wire:key="ca-{{ $ap->id }}">
                                    {{ $ap->approval_no }}{{ $ap->customer_approved_at ? ' · approved '.$ap->customer_approved_at->format('d M Y') : ' · pending' }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    @if ($this->isCustomerApproved)
                        <flux:badge color="lime" size="sm" icon="check-circle">Customer approved</flux:badge>
                    @else
                        <flux:badge color="amber" size="sm">Awaiting customer approval</flux:badge>
                    @endif
                </div>

                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addRecipient">Add vendor</flux:button>
                </div>

                @forelse ($recipients as $r => $rec)
                    <div wire:key="rec-{{ $r }}" class="space-y-2 rounded-md border border-zinc-200 dark:border-zinc-800 p-3">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <flux:select wire:model="recipients.{{ $r }}.vendor_id" variant="listbox" searchable clearable size="sm" label="Vendor" placeholder="Pick a supplier…">
                                @foreach ($this->vendors as $v)
                                    <flux:select.option :value="$v->id" wire:key="rv-{{ $r }}-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="recipients.{{ $r }}.dispatch_channel" variant="listbox" size="sm" clearable label="Send via">
                                @foreach ($VENDOR::dispatchChannels() as $key => $label)
                                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="recipients.{{ $r }}.response_status" variant="listbox" size="sm" label="Reply">
                                @foreach ($VENDOR::responseStatuses() as $key => $label)
                                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <flux:input wire:model="recipients.{{ $r }}.quoted_total" size="sm" type="number" step="0.01" label="Quoted Total" placeholder="0.00" />
                            <flux:input wire:model="recipients.{{ $r }}.lead_time_days" size="sm" type="number" label="Lead Time (days)" placeholder="0" />
                            <flux:input wire:model="recipients.{{ $r }}.warranty_summary" size="sm" label="Warranty" placeholder="e.g. 12 months" />
                            <div class="flex items-end gap-2">
                                <flux:switch wire:model="recipients.{{ $r }}.is_selected" label="Chosen" />
                                <flux:button type="button" size="xs" variant="ghost" icon="trash" wire:click="removeRecipient({{ $r }})" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2">
                            <flux:text size="sm" class="text-zinc-500">
                                @if ($rec['sent_at'])
                                    <flux:icon.check-circle class="inline size-3.5 -mt-0.5 text-lime-500" /> Sent {{ $rec['sent_at'] }}
                                @else
                                    Not sent yet
                                @endif
                            </flux:text>
                            @unless ($rec['sent_at'])
                                <flux:button type="button" size="xs" variant="ghost" icon="paper-airplane" wire:click="markSent({{ $r }})">Mark sent</flux:button>
                            @endunless
                        </div>
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                        No vendors added yet — add at least one to send this inquiry to.
                    </div>
                @endforelse
            </div>
        </section>

        @endif

        <flux:separator />

        {{-- PARTS + QUOTE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Parts & Quote</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Each line is a requested part with the vendor's quote — rate, discount, warranty, lead time.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add part</flux:button>
                </div>

                @foreach ($items as $i => $item)
                    <div wire:key="item-{{ $i }}" class="space-y-2 p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-end">
                            <flux:select wire:model.live="items.{{ $i }}.spare_id" variant="listbox" size="sm" searchable clearable :filter="false" label="Spare" placeholder="Pick from catalogue (optional)…">
                                <x-slot name="search">
                                    <flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.spareSearch" placeholder="Search spare / part no…" />
                                </x-slot>
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
                            <flux:input.group label="Quoted Rate">
                                <flux:input.group.prefix>₹</flux:input.group.prefix>
                                <flux:input wire:model="items.{{ $i }}.quoted_rate" type="number" step="0.01" min="0" size="sm" class:input="text-right font-mono" />
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
                            <flux:select wire:model="items.{{ $i }}.stock_status" variant="listbox" size="sm" clearable label="Availability" placeholder="Stock status…">
                                @foreach ($ITEM::stockStatuses() as $key => $label)
                                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        <flux:select wire:model="items.{{ $i }}.alternative_option" variant="listbox" size="sm" clearable label="Option" placeholder="Primary / Alternate…" class="md:max-w-xs">
                            @foreach ($ITEM::alternativeOptions() as $key => $label)
                                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        {{-- COMPETING QUOTES: several rates for this one part —
                             different vendors, or genuine vs aftermarket. --}}
                        @php($best = $this->bestQuoteIndex($i))
                        <div class="mt-2 rounded-md bg-zinc-50 dark:bg-zinc-900/40 p-2">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <flux:text size="sm" class="font-medium">
                                    Quotes @if (! empty($quotes[$i]))({{ count($quotes[$i]) }})@endif
                                </flux:text>
                                <flux:button type="button" size="xs" variant="ghost" icon="plus" wire:click="addQuote({{ $i }})">Add quote</flux:button>
                            </div>

                            @forelse ($quotes[$i] ?? [] as $q => $quote)
                                <div wire:key="q-{{ $i }}-{{ $q }}"
                                    class="mb-2 rounded-md border p-2 {{ ($quote['is_selected'] ?? false) ? 'border-mq-orange-500 bg-white dark:bg-zinc-900' : 'border-zinc-200 dark:border-zinc-800' }}">
                                    <div class="grid gap-2 {{ $this->showsVendors ? 'grid-cols-2 md:grid-cols-4' : 'grid-cols-2 md:grid-cols-3' }}">
                                        @if ($this->showsVendors)
                                            <flux:select wire:model="quotes.{{ $i }}.{{ $q }}.vendor_id" variant="listbox" size="sm" searchable clearable label="Vendor" placeholder="Which vendor…">
                                                @foreach ($this->vendors as $v)
                                                    <flux:select.option :value="$v->id" wire:key="qv-{{ $i }}-{{ $q }}-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        @endif
                                        <flux:select wire:model="quotes.{{ $i }}.{{ $q }}.part_type_id" variant="listbox" size="sm" clearable label="Grade" placeholder="Genuine / Aftermarket…">
                                            @foreach ($this->partTypes as $pt)
                                                <flux:select.option :value="$pt->id" wire:key="qp-{{ $i }}-{{ $q }}-{{ $pt->id }}">{{ $pt->name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:select wire:model="quotes.{{ $i }}.{{ $q }}.spare_brand_id" variant="listbox" size="sm" searchable clearable label="Brand" placeholder="Brand…">
                                            @foreach ($this->spareBrands as $b)
                                                <flux:select.option :value="$b->id" wire:key="qb-{{ $i }}-{{ $q }}-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:input wire:model.live.debounce.400ms="quotes.{{ $i }}.{{ $q }}.rate" type="number" step="0.01" min="0" size="sm" label="Rate" class:input="text-right font-mono" />
                                    </div>

                                    <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-2">
                                        <flux:input wire:model="quotes.{{ $i }}.{{ $q }}.warranty_period_value" type="number" min="0" size="sm" label="Warranty (months)" />
                                        <flux:input wire:model="quotes.{{ $i }}.{{ $q }}.lead_time_days" type="number" min="0" size="sm" label="Lead time (days)" />
                                        <flux:select wire:model="quotes.{{ $i }}.{{ $q }}.availability" variant="listbox" size="sm" clearable label="Availability" placeholder="—">
                                            @foreach ($QUOTE::availabilities() as $key => $label)
                                                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <div class="flex items-end gap-2">
                                            @if ($quote['is_selected'] ?? false)
                                                <flux:badge color="lime" size="sm" icon="check-circle">Chosen</flux:badge>
                                            @else
                                                <flux:button type="button" size="xs" variant="primary" wire:click="selectQuote({{ $i }}, {{ $q }})">Choose</flux:button>
                                            @endif
                                            @if ($best === $q && ! ($quote['is_selected'] ?? false))
                                                <flux:badge color="sky" size="sm">Cheapest</flux:badge>
                                            @endif
                                            <flux:button type="button" size="xs" variant="ghost" icon="trash" wire:click="removeQuote({{ $i }}, {{ $q }})" />
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <flux:text size="sm" class="text-zinc-500">
                                    No quotes yet. Add one per vendor, or one per grade (genuine vs aftermarket), then choose the one to order.
                                </flux:text>
                            @endforelse
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
                        No extra charges. Click <span class="font-medium">Add charge</span> if the vendor quote includes freight, packing, etc.
                    </div>
                @endforelse
            </div>
        </section>

        <flux:separator />

        {{-- TERMS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Terms & Comparison</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Payment, turnaround, comparison basis and approval.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="payment_term" variant="listbox" clearable label="Payment Terms" placeholder="Advance / COD / Credit…">
                        @foreach ($VPI::paymentTerms() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="comparison_parameter" variant="listbox" clearable label="Compare Quotes By" placeholder="Price / Landed Cost…">
                        @foreach ($VPI::comparisonParameters() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model.live="tat_option" variant="listbox" clearable label="Turnaround (TAT)" placeholder="Expected…">
                        @foreach ($VPI::tatOptions() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div x-show="$wire.tat_option === 'custom'" x-cloak>
                        <flux:input wire:model="tat_custom_days" type="number" min="1" max="365" label="Custom TAT (days)" />
                    </div>
                    <flux:select wire:model="approval_authority" variant="listbox" clearable label="Approval Authority" placeholder="Who approves…">
                        @foreach ($VPI::approvalAuthorities() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="terms_conditions" label="T&Cs / SLA" placeholder="Delivery commitments, penalty rules, service-level terms…" rows="2" />
            </div>
        </section>

        <flux:separator />

        {{-- ATTACHMENTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Attachments</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">RFQ document, vendor quotation, approval note — PDF or image.</flux:text>
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
                        No files yet. Click <span class="font-medium">Add file</span> to attach the RFQ or a vendor quote.
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
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="status" variant="listbox" label="Inquiry Status" required>
                        @foreach ($VPI::statuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="revision_reason_id" variant="listbox" clearable label="Revision Reason" placeholder="If revised…">
                        @foreach ($this->revisionReasons as $vr)
                            <flux:select.option :value="$vr->id" wire:key="vr-{{ $vr->id }}">{{ $vr->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:textarea wire:model="notes" label="Notes" placeholder="Anything the store/vendor should know." rows="2" />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('vendor-purchase-inquiry.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            @if ($this->canEdit)
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create RFQ' }}</flux:button>
            @endif
        </div>
        </fieldset>
    </form>

    {{-- WHAT TO SEND THE VENDOR --}}
    @if ($editingId)
        <flux:modal name="vpi-dispatch" variant="flyout" class="w-full max-w-lg">
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">Message to send</flux:heading>
                    <flux:text size="sm" class="mt-1 text-zinc-500">
                        Copy this to the vendor over WhatsApp or email. The VIN is included so they can identify the exact part.
                    </flux:text>
                </div>

                @if (! $this->dispatchPayload['vin'])
                    <flux:callout variant="warning" icon="exclamation-triangle" heading="No VIN on this vehicle">
                        Vendors usually need the VIN to confirm the right part. Add it on the vehicle record before sending.
                    </flux:callout>
                @endif

                <flux:textarea rows="12" readonly class:input="font-mono text-xs" :value="$this->dispatchPayload['message']" />

                <div>
                    <flux:text size="sm" class="mb-2 font-medium">Images ({{ count($this->dispatchPayload['images']) }})</flux:text>
                    @forelse ($this->dispatchPayload['images'] as $img)
                        <div class="flex items-center justify-between gap-3 rounded-md border border-zinc-200 dark:border-zinc-800 px-3 py-2">
                            <span class="min-w-0 flex-1 truncate text-sm">{{ $img['label'] }}</span>
                            @if ($img['url'])
                                <flux:button size="xs" variant="ghost" icon="arrow-top-right-on-square" :href="$img['url']" target="_blank">Open</flux:button>
                            @endif
                        </div>
                    @empty
                        <flux:text size="sm" class="text-zinc-500">
                            No photos attached. Add an image of the part or plate in Attachments below — it is what stops a vendor sending the wrong part.
                        </flux:text>
                    @endforelse
                </div>

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="ghost">Close</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
