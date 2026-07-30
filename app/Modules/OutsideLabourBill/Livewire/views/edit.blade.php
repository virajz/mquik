@php($OLB = \App\Modules\OutsideLabourBill\Models\OutsideLabourBill::class)
@php($ATT = \App\Modules\OutsideLabourBill\Models\OutsideLabourBillAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('outside-labour-bill.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Outside Labour Bill Verification
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($bill_no ?: 'Edit Bill') : 'Receive Outside Labour Bill' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Record a vendor invoice for outside work and verify it.</flux:text>
        </div>

        <flux:separator />

        {{-- BILL --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Bill</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Vendor, work category and the invoice.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="vendor_id" variant="listbox" searchable clearable :filter="false" label="Vendor / Contractor" placeholder="Who billed…" required autofocus>
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Search vendor…" /></x-slot>
                        @foreach ($this->vendors as $v)
                            <flux:select.option :value="$v->id" wire:key="vn-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="service_specialist_id" variant="listbox" clearable label="Work Category" placeholder="Denting / Painting…">
                        @foreach ($this->workCategories as $wc)
                            <flux:select.option :value="$wc->id" wire:key="wc-{{ $wc->id }}">{{ $wc->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="outside_labour_order_id" variant="listbox" searchable clearable :filter="false" label="OL Order Ref" placeholder="Against order…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="orderSearch" placeholder="Search order…" /></x-slot>
                        @foreach ($this->orders as $o)
                            <flux:select.option :value="$o->id" wire:key="ord-{{ $o->id }}">{{ $o->order_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <flux:select wire:model="bill_document_type" variant="listbox" clearable label="Bill Type" placeholder="Tax Invoice…">
                        @foreach ($OLB::billDocumentTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="vendor_bill_no" label="Vendor Bill No" placeholder="Invoice no" class:input="font-mono" />
                    <flux:date-picker wire:model="bill_date" label="Bill Date" with-today selectable-header fixed-weeks type="input" />
                    <flux:input.group label="Bill Amount">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model="bill_amount" type="number" step="0.01" min="0" class:input="text-right font-mono" />
                    </flux:input.group>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="priority_id" variant="listbox" clearable label="Priority" placeholder="Normal / High…">
                        @foreach ($this->priorities as $p)<flux:select.option :value="$p->id" wire:key="pr-{{ $p->id }}">{{ $p->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="requested_by_id" variant="listbox" searchable clearable label="Requested By" placeholder="Employee…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="rb-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="approved_by_id" variant="listbox" searchable clearable label="Approved By" placeholder="Employee…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="ab-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- LINES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Bill Lines</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Each line is a job card / vehicle covered by the bill.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add line</flux:button>
                </div>

                @foreach ($items as $i => $item)
                    <div wire:key="item-{{ $i }}" class="space-y-2 p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-end">
                            <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Work description (required)" required />
                            <flux:button type="button" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" />
                        </div>
                        <flux:error name="items.{{ $i }}.description" />

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <flux:select wire:model="items.{{ $i }}.job_card_id" variant="listbox" size="sm" searchable clearable :filter="false" label="Job Card" placeholder="JC…">
                                <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.jobCardSearch" placeholder="Search job card…" /></x-slot>
                                @foreach ($this->jobCardOptions($i) as $jc)
                                    <flux:select.option :value="$jc->id" wire:key="jc-{{ $i }}-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.customer_vehicle_id" variant="listbox" size="sm" searchable clearable :filter="false" label="Vehicle" placeholder="Registration…">
                                <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.vehicleSearch" placeholder="Search vehicle…" /></x-slot>
                                @foreach ($this->vehicleOptions($i) as $vh)
                                    <flux:select.option :value="$vh->id" wire:key="vh-{{ $i }}-{{ $vh->id }}">{{ $vh->registration_no }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0.01" size="sm" label="Qty" class:input="text-right font-mono" required />
                            <flux:input.group label="Rate">
                                <flux:input.group.prefix>₹</flux:input.group.prefix>
                                <flux:input wire:model="items.{{ $i }}.rate" type="number" step="0.01" min="0" size="sm" class:input="text-right font-mono" />
                            </flux:input.group>
                            <flux:input.group label="Verified Amount">
                                <flux:input.group.prefix>₹</flux:input.group.prefix>
                                <flux:input wire:model="items.{{ $i }}.verified_amount" type="number" step="0.01" min="0" size="sm" class:input="text-right font-mono" />
                            </flux:input.group>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <flux:separator />

        {{-- VERIFICATION --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Verification</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Completion, status and follow-up.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="work_completion_type" variant="listbox" clearable label="Work Completion" placeholder="Pending / Fully…">
                        @foreach ($OLB::workCompletionTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="status" variant="listbox" label="Verification Status" required>
                        @foreach ($OLB::statuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div x-show="$wire.status === 'on_hold'" x-cloak>
                    <flux:select wire:model="hold_reason" variant="listbox" clearable label="Hold Reason" placeholder="Why on hold" class="md:max-w-sm">
                        @foreach ($OLB::holdReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="hold_reason" />
                </div>
                <div x-show="$wire.status === 'rejected'" x-cloak>
                    <flux:select wire:model="rejection_reason" variant="listbox" clearable label="Rejection Reason" placeholder="Why rejected" class="md:max-w-sm">
                        @foreach ($OLB::rejectionReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="rejection_reason" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <flux:select wire:model="follow_up_mode_id" variant="listbox" clearable label="Follow-up Mode" placeholder="Call / Email…">
                        @foreach ($this->followUpModes as $fm)<flux:select.option :value="$fm->id" wire:key="fm-{{ $fm->id }}">{{ $fm->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model.live="reminder_frequency" variant="listbox" clearable label="Reminder Frequency" placeholder="Config only — never sent">
                        @foreach ($OLB::reminderFrequencies() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div x-show="$wire.reminder_frequency === 'custom'" x-cloak>
                        <flux:input wire:model="reminder_custom_days" type="number" min="1" max="90" label="Every N days" class:input="font-mono" />
                        <flux:error name="reminder_custom_days" />
                    </div>
                </div>
                <flux:select wire:model="vendor_rating_type" variant="listbox" clearable label="Vendor Rating On" placeholder="Quality / Price…" class="md:max-w-sm">
                    @foreach ($OLB::vendorRatingTypes() as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </section>

        <flux:separator />

        {{-- ATTACHMENTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Attachments</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Original / duplicate invoice copy, WhatsApp screenshot.</flux:text>
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

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Verification remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('outside-labour-bill.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Receive bill' }}</flux:button>
        </div>
    </form>
</div>
