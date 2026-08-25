@php($GRN = \App\Modules\GoodsReceipt\Models\GoodsReceipt::class)
@php($ITEM = \App\Modules\GoodsReceipt\Models\GoodsReceiptItem::class)
@php($ATT = \App\Modules\GoodsReceipt\Models\GoodsReceiptAttachment::class)
<div>
    <form wire:submit="save" novalidate class="max-w-4xl">
        <div class="mb-8 flex items-start justify-between gap-4">
            <div>
                <flux:link :href="route('goods-receipt.index')" variant="ghost" class="text-xs">
                    <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Goods Receive & Verification
                </flux:link>
                <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($grn_no ?: 'Edit GRN') : 'Receive Goods' }}</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Receive vendor parts and verify each line against the order.</flux:text>
            </div>
            @if ($editingId)
                @can('goods_handover.create')
                    <flux:button :href="route('goods-handover.create', ['from-grn' => $editingId])" wire:navigate size="sm" variant="ghost" icon="arrow-right-circle" class="shrink-0">
                        Hand over to floor
                    </flux:button>
                @endcan
            @endif
        </div>

        <flux:separator />

        {{-- RECEIPT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Receipt</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Vendor, source and who received it.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model.live="goods_receipt_type" variant="listbox" label="Receipt Type" required autofocus>
                        @foreach ($GRN::receiptTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="vendor_id" variant="listbox" searchable clearable :filter="false" label="Vendor" placeholder="Supplier…" required>
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Search vendor…" /></x-slot>
                        @foreach ($this->vendors as $v)<flux:select.option :value="$v->id" wire:key="vn-{{ $v->id }}">{{ $v->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="delivery_performance" variant="listbox" clearable label="Delivery Performance" placeholder="On time / Delayed">
                        @foreach ($GRN::deliveryPerformances() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3" x-show="$wire.goods_receipt_type === 'against_po'" x-cloak>
                    <div>
                        <flux:select wire:model="vendor_purchase_order_id" variant="listbox" searchable clearable :filter="false" label="Purchase Order" placeholder="Against PO…">
                            <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="poSearch" placeholder="Search PO…" /></x-slot>
                            @foreach ($this->purchaseOrders as $po)<flux:select.option :value="$po->id" wire:key="po-{{ $po->id }}">{{ $po->po_no }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:error name="vendor_purchase_order_id" />
                    </div>
                    <flux:select wire:model="vendor_purchase_inquiry_id" variant="listbox" searchable clearable :filter="false" label="Purchase Inquiry" placeholder="VPI…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="inquirySearch" placeholder="Search VPI…" /></x-slot>
                        @foreach ($this->inquiries as $iq)<flux:select.option :value="$iq->id" wire:key="iq-{{ $iq->id }}">{{ $iq->vpi_no }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="received_by_id" variant="listbox" searchable clearable label="Store — Received By" placeholder="Employee…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="rb-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="verified_by_id" variant="listbox" searchable clearable label="Store — Verified By" placeholder="Employee…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="vb-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ITEMS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Received Lines</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">
                    {{ $fullDetail
                        ? 'Full detail: rates, approvals, storage bin and photos.'
                        : 'Tick off what arrived. Switch to full detail for rates, approvals and bins.' }}
                </flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex items-center justify-between gap-3">
                    <flux:switch wire:model.live="fullDetail" label="Full detail" />
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add part</flux:button>
                </div>

                @foreach ($items as $i => $item)
                    @if (! $fullDetail)
                        {{-- CHECKLIST: the four things checked at the counter. --}}
                        <div wire:key="chk-{{ $i }}" class="flex flex-wrap items-end gap-3 rounded-md border border-zinc-200 dark:border-zinc-800 p-3">
                            <div class="min-w-0 flex-1">
                                <flux:input wire:model="items.{{ $i }}.description" size="sm" label="Part" placeholder="Part description" required />
                            </div>
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0.01" size="sm"
                                label="Qty" class="w-24" class:input="text-right font-mono" required />
                            <flux:select wire:model="items.{{ $i }}.material_condition" variant="listbox" size="sm" label="Condition" class="w-36">
                                @foreach ($ITEM::materialConditions() as $key => $label)
                                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.physical_verification" variant="listbox" size="sm" clearable label="Checked" class="w-36" placeholder="Not yet">
                                @foreach ($ITEM::physicalVerifications() as $key => $label)
                                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:button type="button" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" class="h-9!" />
                            <div class="w-full">
                                <flux:error name="items.{{ $i }}.description" />
                            </div>
                        </div>
                    @else
                    <div wire:key="item-{{ $i }}" class="space-y-2 p-3 rounded-md border border-zinc-200 dark:border-zinc-800" x-data>
                        <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-end">
                            <flux:select wire:model.live="items.{{ $i }}.spare_id" variant="listbox" size="sm" searchable clearable :filter="false" label="Spare" placeholder="Pick from catalogue…">
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
                                @foreach ($this->spareBrands as $b)<flux:select.option :value="$b->id" wire:key="sb-{{ $i }}-{{ $b->id }}">{{ $b->name }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.uom_id" variant="listbox" size="sm" clearable label="UOM" placeholder="Unit…">
                                @foreach ($this->uoms as $u)<flux:select.option :value="$u->id" wire:key="uom-{{ $i }}-{{ $u->id }}">{{ $u->code ?? $u->name }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0.01" size="sm" label="Qty" class:input="text-right font-mono" required />
                            <flux:input.group label="Rate">
                                <flux:input.group.prefix>₹</flux:input.group.prefix>
                                <flux:input wire:model="items.{{ $i }}.rate" type="number" step="0.01" min="0" size="sm" class:input="text-right font-mono" />
                            </flux:input.group>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <flux:select wire:model="items.{{ $i }}.job_card_id" variant="listbox" size="sm" searchable clearable :filter="false" label="Job Card" placeholder="JC…">
                                <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.jobCardSearch" placeholder="Search job card…" /></x-slot>
                                @foreach ($this->jobCardOptions($i) as $jc)<flux:select.option :value="$jc->id" wire:key="jc-{{ $i }}-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.customer_vehicle_id" variant="listbox" size="sm" searchable clearable :filter="false" label="Vehicle" placeholder="Reg…">
                                <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.vehicleSearch" placeholder="Search vehicle…" /></x-slot>
                                @foreach ($this->vehicleOptions($i) as $vh)<flux:select.option :value="$vh->id" wire:key="vh-{{ $i }}-{{ $vh->id }}">{{ $vh->registration_no }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.workshop_department_id" variant="listbox" size="sm" clearable label="Department" placeholder="Dept…">
                                @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="dp-{{ $i }}-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:input wire:model="items.{{ $i }}.storage_allocation" size="sm" label="Storage Bin" placeholder="A1 / B3…" class:input="font-mono uppercase" />
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <flux:select wire:model="items.{{ $i }}.material_condition" variant="listbox" size="sm" clearable label="Material Condition" placeholder="New / Used…">
                                @foreach ($ITEM::materialConditions() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model.live="items.{{ $i }}.physical_verification" variant="listbox" size="sm" clearable label="Physical Verification" placeholder="OK / Damage…">
                                @foreach ($ITEM::physicalVerifications() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.damage_type" variant="listbox" size="sm" clearable label="Damage Type" placeholder="If damaged…">
                                @foreach ($ITEM::damageTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                            </flux:select>
                            <div>
                                <flux:input type="file" wire:model="photoFiles.{{ $i }}" size="sm" label="Spare Photo" accept=".jpg,.jpeg,.png,.webp" />
                                @if (! empty($item['photo_path']))<flux:text size="sm" class="text-zinc-500 mt-1">Uploaded ✓</flux:text>@endif
                                <flux:error name="photoFiles.{{ $i }}" />
                            </div>
                        </div>

                        {{-- Floor + per-line approval --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 pt-1 border-t border-zinc-100 dark:border-zinc-800">
                            <flux:select wire:model="items.{{ $i }}.floor_received_by_id" variant="listbox" size="sm" searchable clearable label="Floor — Received (Tech)" placeholder="Technician…">
                                @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="fr-{{ $i }}-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.floor_verified_by_id" variant="listbox" size="sm" searchable clearable label="Floor — Verified (Adv)" placeholder="Advisor…">
                                @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="fv-{{ $i }}-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:input wire:model="items.{{ $i }}.quantity_approved" type="number" step="0.01" min="0" size="sm" label="Qty Approved" class:input="text-right font-mono" />
                            <flux:input.group label="Rate Approved">
                                <flux:input.group.prefix>₹</flux:input.group.prefix>
                                <flux:input wire:model="items.{{ $i }}.rate_approved" type="number" step="0.01" min="0" size="sm" class:input="text-right font-mono" />
                            </flux:input.group>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 items-end">
                            <flux:input wire:model="items.{{ $i }}.discount_approved" type="number" step="0.01" min="0" size="sm" label="Disc. Approved" class:input="text-right font-mono" />
                            <flux:input wire:model="items.{{ $i }}.tat_approved" size="sm" label="TAT Approved" placeholder="e.g. 3 days" />
                            <flux:input.group label="Last Purchase">
                                <flux:input.group.prefix>₹</flux:input.group.prefix>
                                <flux:input wire:model="items.{{ $i }}.last_purchase_price" type="number" step="0.01" min="0" size="sm" class:input="text-right font-mono" />
                            </flux:input.group>
                            <flux:checkbox wire:model="items.{{ $i }}.part_approved" label="Part Approved" />
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </section>

        <flux:separator />

        {{-- STATUS & EVIDENCE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Status & Evidence</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Approval authority, GRN status and footer evidence.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="approval_authority" variant="listbox" clearable label="Approval Authority" placeholder="Store Exec / Parts Mgr…">
                        @foreach ($GRN::approvalAuthorities() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="vendor_category" variant="listbox" clearable label="Vendor Category" placeholder="Preferred…">
                        @foreach ($GRN::vendorCategories() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="status" variant="listbox" label="GRN Status" required>
                        @foreach ($GRN::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="follow_up_mode_id" variant="listbox" clearable label="Follow-up Mode" placeholder="Call / Email…">
                        @foreach ($this->followUpModes as $fm)<flux:select.option :value="$fm->id" wire:key="fm-{{ $fm->id }}">{{ $fm->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="vendor_rating_type" variant="listbox" clearable label="Vendor Rating On" placeholder="Quality / Price…">
                        @foreach ($GRN::vendorRatingTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>

                {{-- Footer attachments --}}
                <div class="pt-2">
                    <div class="flex items-center justify-between mb-2">
                        <flux:text size="sm" class="font-medium">Additional Evidence</flux:text>
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addAttachment">Add file</flux:button>
                    </div>
                    @forelse ($attachments as $i => $att)
                        <div wire:key="att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2 items-end mb-2 p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                            <flux:select wire:model="attachments.{{ $i }}.attachment_type" variant="listbox" size="sm" clearable label="Type" placeholder="Type…">
                                @foreach ($ATT::attachmentTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                            </flux:select>
                            <div>
                                <flux:input type="file" wire:model="attachmentFiles.{{ $i }}" size="sm" accept=".jpg,.jpeg,.png,.webp,.pdf" />
                                @if (! empty($att['path']))<flux:text size="sm" class="text-zinc-500 mt-1">Current: {{ $att['original_name'] ?? basename($att['path']) }}</flux:text>@endif
                                <flux:error name="attachmentFiles.{{ $i }}" />
                            </div>
                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeAttachment({{ $i }})" class="h-9!" />
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Damage photo / fault evidence / invoice copy.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Receipt / verification remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('goods-receipt.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Receive goods' }}</flux:button>
        </div>
    </form>
</div>
