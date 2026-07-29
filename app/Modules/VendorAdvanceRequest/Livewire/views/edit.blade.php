@php($VAR = \App\Modules\VendorAdvanceRequest\Models\VendorAdvanceRequest::class)
@php($ATT = \App\Modules\VendorAdvanceRequest\Models\VendorAdvanceRequestAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('vendor-advance-request.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Vendor Advance Request
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($request_no ?: 'Edit Advance Request') : 'New Advance Request' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Request an advance to a vendor for a special order.</flux:text>
        </div>

        <flux:separator />

        {{-- VENDOR & CONTEXT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Vendor & Context</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Who, against which order.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="vendor_id" variant="listbox" searchable clearable :filter="false" label="Vendor" placeholder="Supplier…" required autofocus>
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Search vendor…" /></x-slot>
                        @foreach ($this->vendors as $v)
                            <flux:select.option :value="$v->id" wire:key="vn-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="vendor_category" variant="listbox" clearable label="Vendor Category" placeholder="Preferred / Approved…">
                        @foreach ($VAR::vendorCategories() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
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
                    <flux:select wire:model="vpo_approval_id" variant="listbox" clearable label="PO Approval Ref" placeholder="Approved PO…">
                        @foreach ($this->poApprovals as $pa)
                            <flux:select.option :value="$pa->id" wire:key="pa-{{ $pa->id }}">{{ $pa->approval_no }}</flux:select.option>
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
                    <flux:select wire:model="parts_category" variant="listbox" clearable label="Parts Category" placeholder="Genuine / Aftermarket…">
                        @foreach ($VAR::partsCategories() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="priority_id" variant="listbox" clearable label="Priority" placeholder="Normal / High…">
                        @foreach ($this->priorities as $p)
                            <flux:select.option :value="$p->id" wire:key="pr-{{ $p->id }}">{{ $p->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ADVANCE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Advance</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Reason, amount and how it will be paid.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="advance_reason" variant="listbox" clearable label="Advance Reason" placeholder="Why an advance…">
                        @foreach ($VAR::advanceReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input.group label="Amount">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model="amount" type="number" step="0.01" min="0" class:input="text-right font-mono" placeholder="0.00" />
                    </flux:input.group>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="payment_mode" variant="listbox" clearable label="Payment Mode" placeholder="Cash / NEFT / …">
                        @foreach ($VAR::paymentModes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="bank_id" variant="listbox" clearable label="Bank" placeholder="Paying bank…">
                        @foreach ($this->banks as $b)
                            <flux:select.option :value="$b->id" wire:key="bk-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- DOCUMENT CHECKLIST --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Document Checklist</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">What must be on file before release.</flux:text>
            </div>
            <div class="space-y-2 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addDocument">Add document</flux:button>
                </div>
                @forelse ($documents as $i => $doc)
                    <div wire:key="doc-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[auto_1fr_auto] gap-3 items-center p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <flux:checkbox wire:model="documents.{{ $i }}.is_provided" />
                        <flux:input wire:model="documents.{{ $i }}.document_name" size="sm" placeholder="Document name" />
                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeDocument({{ $i }})" />
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">No documents listed.</div>
                @endforelse
                <flux:error name="documents" />
            </div>
        </section>

        <flux:separator />

        {{-- STATUS & FOLLOW-UP --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Status & Follow-up</flux:heading>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="status" variant="listbox" label="Status" required>
                        @foreach ($VAR::statuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="follow_up_mode_id" variant="listbox" clearable label="Follow-up Mode" placeholder="Call / Email / …">
                        @foreach ($this->followUpModes as $fm)
                            <flux:select.option :value="$fm->id" wire:key="fm-{{ $fm->id }}">{{ $fm->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div x-show="$wire.status === 'on_hold'" x-cloak>
                    <flux:select wire:model="hold_reason" variant="listbox" clearable label="Hold Reason" placeholder="Why on hold" class="md:max-w-sm">
                        @foreach ($VAR::holdReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="hold_reason" />
                </div>
                <div x-show="$wire.status === 'rejected'" x-cloak>
                    <flux:select wire:model="rejection_reason" variant="listbox" clearable label="Rejection Reason" placeholder="Why rejected" class="md:max-w-sm">
                        @foreach ($VAR::rejectionReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="rejection_reason" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end">
                    <flux:select wire:model.live="reminder_frequency" variant="listbox" clearable label="Reminder Frequency" placeholder="Config only — never sent">
                        @foreach ($VAR::reminderFrequencies() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div x-show="$wire.reminder_frequency === 'custom'" x-cloak>
                        <flux:input wire:model="reminder_custom_days" type="number" min="1" max="90" label="Every N days" placeholder="e.g. 5" class:input="font-mono" />
                        <flux:error name="reminder_custom_days" />
                    </div>
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
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Cheque copy / UTR / deposit slip.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Request remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('vendor-advance-request.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create request' }}</flux:button>
        </div>
    </form>
</div>
