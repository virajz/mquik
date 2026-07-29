@php($VPA = \App\Modules\VpoApproval\Models\VpoApproval::class)
@php($ATT = \App\Modules\VpoApproval\Models\VpoApprovalAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('vpo-approval.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> VPO Approval
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($approval_no ?: 'Edit PO Approval') : 'New PO Approval' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Admin approval for a vendor purchase order.</flux:text>
        </div>

        <flux:separator />

        {{-- PO DETAILS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">PO Details</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Type, vendor and inquiry.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="po_approval_type" variant="listbox" label="PO Approval Type" required autofocus>
                        @foreach ($VPA::poApprovalTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="vendor_id" variant="listbox" searchable clearable :filter="false" label="Vendor" placeholder="Supplier…" required>
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Search vendor…" /></x-slot>
                        @foreach ($this->vendors as $v)
                            <flux:select.option :value="$v->id" wire:key="vn-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="vendor_purchase_inquiry_id" variant="listbox" searchable clearable :filter="false" label="VPI / RFQ Ref" placeholder="From an RFQ…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="inquirySearch" placeholder="Search VPI…" /></x-slot>
                        @foreach ($this->inquiries as $iq)
                            <flux:select.option :value="$iq->id" wire:key="iq-{{ $iq->id }}">{{ $iq->vpi_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="part_type_id" variant="listbox" clearable label="Inventory Type" placeholder="Genuine / Aftermarket…">
                        @foreach ($this->partTypes as $pt)
                            <flux:select.option :value="$pt->id" wire:key="pt-{{ $pt->id }}">{{ $pt->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="priority_id" variant="listbox" clearable label="Priority" placeholder="Normal / High…">
                        @foreach ($this->priorities as $p)
                            <flux:select.option :value="$p->id" wire:key="pr-{{ $p->id }}">{{ $p->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3" x-show="['odd_item','high_value','job_card'].includes($wire.po_approval_type)" x-cloak>
                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="Required for odd/high value…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                        @foreach ($this->jobCards as $jc)
                            <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="job_card_id" />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- LINE ITEMS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Line Items</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Per-line approval — qty, rate, discount, TAT.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add part</flux:button>
                </div>
                @foreach ($items as $i => $item)
                    <div wire:key="item-{{ $i }}" class="space-y-2 p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-end">
                            <flux:select wire:model.live="items.{{ $i }}.spare_id" variant="listbox" size="sm" searchable clearable :filter="false" label="Spare" placeholder="Pick a spare…">
                                <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.spareSearch" placeholder="Search…" /></x-slot>
                                @foreach ($this->spareOptions($i) as $sp)
                                    <flux:select.option :value="$sp->id" wire:key="sp-{{ $i }}-{{ $sp->id }}">{{ $sp->name }} <span class="text-zinc-400">({{ $sp->spare_code }})</span></flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:button type="button" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" />
                        </div>
                        <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Part description (required)" required />
                        <flux:error name="items.{{ $i }}.description" />
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0.01" size="sm" label="Qty" class:input="text-right font-mono" required />
                            <flux:input wire:model="items.{{ $i }}.qty_approved" type="number" step="0.01" min="0" size="sm" label="Qty Appr." class:input="text-right font-mono" />
                            <flux:input.group label="Rate Appr.">
                                <flux:input.group.prefix>₹</flux:input.group.prefix>
                                <flux:input wire:model="items.{{ $i }}.rate_approved" type="number" step="0.01" min="0" size="sm" class:input="text-right font-mono" />
                            </flux:input.group>
                            <flux:input wire:model="items.{{ $i }}.discount_approved" type="number" step="0.01" min="0" size="sm" label="Disc. Appr." class:input="text-right font-mono" />
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 items-end">
                            <flux:input wire:model="items.{{ $i }}.tat_approved" size="sm" label="TAT Appr." placeholder="e.g. 3 days" />
                            <flux:input.group label="Last Purchase">
                                <flux:input.group.prefix>₹</flux:input.group.prefix>
                                <flux:input wire:model="items.{{ $i }}.last_purchase_price" type="number" step="0.01" min="0" size="sm" class:input="text-right font-mono" />
                            </flux:input.group>
                            <flux:input wire:model="items.{{ $i }}.last_purchase_vendor" size="sm" label="Last Vendor" placeholder="Vendor" />
                            <flux:checkbox wire:model="items.{{ $i }}.part_approved" label="Part Approved" />
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <flux:separator />

        {{-- CHARGES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Additional Charges</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Freight, P&F, packing, etc.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addCharge">Add charge</flux:button>
                </div>
                @forelse ($charges as $i => $charge)
                    <div wire:key="charge-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_180px_auto] gap-2 items-end p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <flux:select wire:model="charges.{{ $i }}.charge_type_id" variant="listbox" size="sm" searchable clearable label="Charge" placeholder="Head…">
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
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">No extra charges.</div>
                @endforelse
            </div>
        </section>

        <flux:separator />

        {{-- TERMS & STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Terms & Approval</flux:heading>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="approval_level" variant="listbox" clearable label="Approval Level" placeholder="L1 / L2 / L3">
                        @foreach ($VPA::approvalLevels() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="approval_mode_id" variant="listbox" clearable label="Approval Mode" placeholder="System / Email / …">
                        @foreach ($this->approvalModes as $am)
                            <flux:select.option :value="$am->id" wire:key="am-{{ $am->id }}">{{ $am->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="payment_term" variant="listbox" clearable label="Payment Terms" placeholder="Advance / Credit…">
                        @foreach ($VPA::paymentTerms() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="vendor_category" variant="listbox" clearable label="Vendor Category" placeholder="Preferred / Approved…">
                        @foreach ($VPA::vendorCategories() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="status" variant="listbox" label="PO Approval Status" required>
                        @foreach ($VPA::statuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div x-show="$wire.status === 'rejected'" x-cloak>
                    <flux:select wire:model="rejection_reason" variant="listbox" clearable label="Rejection Reason" placeholder="Why rejected" class="md:max-w-sm">
                        @foreach ($VPA::rejectionReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="rejection_reason" />
                </div>

                {{-- Attachments --}}
                <div class="pt-2">
                    <div class="flex items-center justify-between mb-2">
                        <flux:text size="sm" class="font-medium">Attachments</flux:text>
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addAttachment">Add file</flux:button>
                    </div>
                    @forelse ($attachments as $i => $att)
                        <div wire:key="att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2 items-end mb-2 p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                            <flux:select wire:model="attachments.{{ $i }}.attachment_type" variant="listbox" size="sm" clearable label="Type" placeholder="Type…">
                                @foreach ($ATT::attachmentTypes() as $key => $label)
                                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <div>
                                <flux:input type="file" wire:model="attachmentFiles.{{ $i }}" size="sm" accept=".jpg,.jpeg,.png,.webp,.pdf" />
                                @if (! empty($att['path']))<flux:text size="sm" class="text-zinc-500 mt-1">Current: {{ $att['original_name'] ?? basename($att['path']) }}</flux:text>@endif
                                <flux:error name="attachmentFiles.{{ $i }}" />
                            </div>
                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeAttachment({{ $i }})" class="h-9!" />
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Vendor quotation / comparison / approval note.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Approval remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('vpo-approval.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create approval' }}</flux:button>
        </div>
    </form>
</div>
