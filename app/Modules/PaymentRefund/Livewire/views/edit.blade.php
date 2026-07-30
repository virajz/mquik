@php($PR = \App\Modules\PaymentRefund\Models\PaymentRefund::class)
@php($ATT = \App\Modules\PaymentRefund\Models\PaymentRefundAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('payment-refund.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Payment Refund
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($refund_no ?: 'Edit Refund') : 'New Payment Refund' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Record a refund received from a vendor.</flux:text>
        </div>

        <flux:separator />

        {{-- REFUND --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Refund</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Vendor, type and amount.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="vendor_id" variant="listbox" searchable clearable :filter="false" label="Vendor" placeholder="Who refunds…" required autofocus>
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Search vendor…" /></x-slot>
                        @foreach ($this->vendors as $v)<flux:select.option :value="$v->id" wire:key="vn-{{ $v->id }}">{{ $v->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="refund_against" variant="listbox" clearable label="Refund Against" placeholder="Advance / Regular…">
                        @foreach ($PR::refundAgainstOptions() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="refund_type" variant="listbox" clearable label="Refund Type" placeholder="Excess / Duplicate…">
                        @foreach ($PR::refundTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <flux:select wire:model="priority" variant="listbox" label="Priority" required>
                        @foreach ($PR::priorities() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="refund_by_id" variant="listbox" searchable clearable label="Refund By" placeholder="Employee…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="rb-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="store_incharge_id" variant="listbox" searchable clearable label="Store In-charge" placeholder="Store…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="si-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="advisor_id" variant="listbox" searchable clearable label="Service Advisor" placeholder="Advisor…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="ad-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- REFERENCES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Source References</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">What the refund is against.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model.live="advance_payment_id" variant="listbox" searchable clearable :filter="false" label="Advance Payment" placeholder="MQ/AP…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="advanceSearch" placeholder="Search advance…" /></x-slot>
                        @foreach ($this->advancePayments as $ap)<flux:select.option :value="$ap->id" wire:key="ap-{{ $ap->id }}">{{ $ap->payment_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="vendor_purchase_order_id" variant="listbox" searchable clearable :filter="false" label="Purchase Order / Quote" placeholder="VPO…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="poSearch" placeholder="Search PO…" /></x-slot>
                        @foreach ($this->purchaseOrders as $po)<flux:select.option :value="$po->id" wire:key="po-{{ $po->id }}">{{ $po->po_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input wire:model="regular_payment_reference" label="Regular Payment Ref" placeholder="Payment ref" class:input="font-mono" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="goods_return_note_id" variant="listbox" searchable clearable :filter="false" label="Spares Purchase Return" placeholder="GRTN…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="grnSearch" placeholder="Search return…" /></x-slot>
                        @foreach ($this->goodsReturnNotes as $g)<flux:select.option :value="$g->id" wire:key="grtn-{{ $g->id }}">{{ $g->return_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="outside_labour_return_id" variant="listbox" searchable clearable :filter="false" label="Outside Labour Return" placeholder="OLRR…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="olrSearch" placeholder="Search return…" /></x-slot>
                        @foreach ($this->outsideLabourReturns as $o)<flux:select.option :value="$o->id" wire:key="olr-{{ $o->id }}">{{ $o->return_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input wire:model="purchase_invoice_reference" label="Purchase Invoice Ref" placeholder="Invoice no" class:input="font-mono" />
                </div>
                <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="If job-related…" class="md:max-w-sm">
                    <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                    @foreach ($this->jobCards as $jc)<flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>@endforeach
                </flux:select>
            </div>
        </section>

        <flux:separator />

        {{-- MONEY --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Money</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Amount, mode and instrument.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input.group label="Refund Amount">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model="amount" type="number" step="0.01" min="0" class:input="text-right font-mono" />
                    </flux:input.group>
                    <flux:select wire:model.live="refund_mode" variant="listbox" clearable label="Refund Mode" placeholder="Cash / NEFT / …">
                        @foreach ($PR::refundModes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="bank_id" variant="listbox" clearable label="Bank" placeholder="Receiving bank…">
                        @foreach ($this->banks as $b)<flux:select.option :value="$b->id" wire:key="bk-{{ $b->id }}">{{ $b->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <flux:input wire:model="reference_no" label="Reference / UTR No" placeholder="Transaction reference" class:input="font-mono" class="md:max-w-sm" />

                {{-- Cheque block --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <flux:input wire:model="cheque_no" label="Cheque No" placeholder="If by cheque" class:input="font-mono" />
                    <flux:date-picker wire:model="cheque_date" label="Cheque Date" with-today selectable-header fixed-weeks type="input" />
                    <flux:select wire:model.live="cheque_status" variant="listbox" clearable label="Cheque Status" placeholder="Cleared / Bounce">
                        @foreach ($PR::chequeStatuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div x-show="$wire.cheque_status === 'bounce'" x-cloak>
                    <flux:select wire:model="cheque_bounce_reason_id" variant="listbox" clearable label="Cheque Bounce Reason" placeholder="Why bounced" class="md:max-w-sm">
                        @foreach ($this->chequeBounceReasons as $cb)<flux:select.option :value="$cb->id" wire:key="cb-{{ $cb->id }}">{{ $cb->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:error name="cheque_bounce_reason_id" />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Status</flux:heading>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <flux:select wire:model.live="status" variant="listbox" label="Refund Status" required class="md:max-w-sm">
                    @foreach ($PR::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                </flux:select>
                <div x-show="$wire.status === 'on_hold'" x-cloak>
                    <flux:select wire:model="hold_reason" variant="listbox" clearable label="Hold Reason" placeholder="Why on hold" class="md:max-w-sm">
                        @foreach ($PR::holdReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:error name="hold_reason" />
                </div>
                <div x-show="$wire.status === 'rejected'" x-cloak>
                    <flux:select wire:model="rejection_reason" variant="listbox" clearable label="Rejection Reason" placeholder="Why rejected" class="md:max-w-sm">
                        @foreach ($PR::rejectionReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:error name="rejection_reason" />
                </div>
                <div x-show="$wire.status === 'cancelled'" x-cloak>
                    <flux:select wire:model="cancellation_reason" variant="listbox" clearable label="Cancellation Reason" placeholder="Why cancelled" class="md:max-w-sm">
                        @foreach ($PR::cancellationReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:error name="cancellation_reason" />
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
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Excess payment proof / cheque copy / UTR / payment advice.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Refund remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('payment-refund.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Record refund' }}</flux:button>
        </div>
    </form>
</div>
