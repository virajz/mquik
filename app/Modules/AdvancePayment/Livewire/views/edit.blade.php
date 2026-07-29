@php($AP = \App\Modules\AdvancePayment\Models\AdvancePayment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('advance-payment.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Advance Payment Entry
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($payment_no ?: 'Edit Advance Payment') : 'New Advance Payment' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Record an advance paid to a vendor.</flux:text>
        </div>

        <flux:separator />

        {{-- VENDOR & CONTEXT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Vendor & Context</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Who is paid, against which request.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="vendor_id" variant="listbox" searchable clearable :filter="false" label="Vendor" placeholder="Supplier…" required autofocus>
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Search vendor…" /></x-slot>
                        @foreach ($this->vendors as $v)
                            <flux:select.option :value="$v->id" wire:key="vn-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="advance_payment_type" variant="listbox" clearable label="Advance Type" placeholder="Against request / Direct…">
                        @foreach ($AP::advancePaymentTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="vendor_advance_request_id" variant="listbox" searchable clearable :filter="false" label="Advance Request" placeholder="VAR ref…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="requestSearch" placeholder="Search request…" /></x-slot>
                        @foreach ($this->advanceRequests as $ar)
                            <flux:select.option :value="$ar->id" wire:key="ar-{{ $ar->id }}">{{ $ar->request_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="vendor_purchase_inquiry_id" variant="listbox" searchable clearable :filter="false" label="VPI Ref" placeholder="From an RFQ…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="inquirySearch" placeholder="Search VPI…" /></x-slot>
                        @foreach ($this->inquiries as $iq)
                            <flux:select.option :value="$iq->id" wire:key="iq-{{ $iq->id }}">{{ $iq->vpi_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="If job-related…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                        @foreach ($this->jobCards as $jc)
                            <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="customer_id" variant="listbox" searchable clearable :filter="false" label="Customer" placeholder="If applicable…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="customerSearch" placeholder="Search customer…" /></x-slot>
                        @foreach ($this->customers as $c)
                            <flux:select.option :value="$c->id" wire:key="cu-{{ $c->id }}">{{ trim($c->first_name.' '.$c->last_name) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="customer_vehicle_id" variant="listbox" searchable clearable :filter="false" label="Vehicle" placeholder="Registration…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Search vehicle…" /></x-slot>
                        @foreach ($this->vehicles as $vh)
                            <flux:select.option :value="$vh->id" wire:key="vh-{{ $vh->id }}">{{ $vh->registration_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="workshop_department_id" variant="listbox" clearable label="Department" placeholder="Workshop dept…">
                        @foreach ($this->departments as $d)
                            <flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="service_type_id" variant="listbox" clearable label="Service Type" placeholder="Type…">
                        @foreach ($this->serviceTypes as $st)
                            <flux:select.option :value="$st->id" wire:key="st-{{ $st->id }}">{{ $st->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- PAYMENT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Payment</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Amount, mode and instrument.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input.group label="Amount">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model="amount" type="number" step="0.01" min="0.01" class:input="text-right font-mono" placeholder="0.00" required />
                    </flux:input.group>
                    <flux:select wire:model.live="payment_mode_id" variant="listbox" label="Payment Mode" placeholder="Cash / NEFT / …" required>
                        @foreach ($this->paymentModes as $pm)
                            <flux:select.option :value="$pm->id" wire:key="pm-{{ $pm->id }}">{{ $pm->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="bank_id" variant="listbox" clearable label="Bank" placeholder="Paying bank…">
                        @foreach ($this->banks as $b)
                            <flux:select.option :value="$b->id" wire:key="bk-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input wire:model="reference_no" label="Reference / UTR No" placeholder="Transaction reference" class:input="font-mono" />
                    <flux:input wire:model="paid_at" type="datetime-local" label="Paid At" />
                </div>

                {{-- Cheque block --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <flux:input wire:model="cheque_no" label="Cheque No" placeholder="If by cheque" class:input="font-mono" />
                    <flux:input wire:model="cheque_date" type="date" label="Cheque Date" />
                    <flux:select wire:model.live="cheque_status" variant="listbox" clearable label="Cheque Status" placeholder="Issued / Cleared…">
                        @foreach ($AP::chequeStatuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div x-show="$wire.cheque_status === 'returned_bounced'" x-cloak>
                    <flux:select wire:model="cheque_bounce_reason_id" variant="listbox" clearable label="Cheque Bounce Reason" placeholder="Why bounced" class="md:max-w-sm">
                        @foreach ($this->chequeBounceReasons as $cb)
                            <flux:select.option :value="$cb->id" wire:key="cb-{{ $cb->id }}">{{ $cb->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- HANDLED BY --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Handled By</flux:heading>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 min-w-0">
                <flux:select wire:model="entry_by_id" variant="listbox" clearable label="Entry By" placeholder="Cashier…">
                    @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="eb-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select wire:model="store_incharge_id" variant="listbox" clearable label="Store In-charge" placeholder="Store…">
                    @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="si-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select wire:model="advisor_id" variant="listbox" clearable label="Service Advisor" placeholder="Advisor…">
                    @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="ad-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                </flux:select>
            </div>
        </section>

        <flux:separator />

        {{-- STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Status</flux:heading>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <flux:select wire:model.live="payment_status" variant="listbox" label="Payment Status" required class="md:max-w-sm">
                    @foreach ($AP::paymentStatuses() as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <div x-show="$wire.payment_status === 'reversed'" x-cloak>
                    <flux:select wire:model="reversal_reason" variant="listbox" clearable label="Reversal Reason" placeholder="Why reversed" class="md:max-w-sm">
                        @foreach ($AP::reversalReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="reversal_reason" />
                </div>
                <div x-show="$wire.payment_status === 'cancelled'" x-cloak>
                    <flux:select wire:model="cancellation_reason" variant="listbox" clearable label="Cancellation Reason" placeholder="Why cancelled" class="md:max-w-sm">
                        @foreach ($AP::cancellationReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
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
                                @foreach ($AP::attachmentTypes() as $key => $label)
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
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Cheque copy / UTR / deposit slip.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Payment remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('advance-payment.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Record payment' }}</flux:button>
        </div>
    </form>
</div>
