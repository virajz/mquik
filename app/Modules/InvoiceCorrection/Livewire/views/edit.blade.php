@php($IC = \App\Modules\InvoiceCorrection\Models\InvoiceCorrection::class)
@php($ITEM = \App\Modules\InvoiceCorrection\Models\InvoiceCorrectionItem::class)
@php($ATT = \App\Modules\InvoiceCorrection\Models\InvoiceCorrectionAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('invoice-correction.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Invoice Correction
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($correction_no ?: 'Edit Correction') : 'New Invoice Correction' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Request a correction to a raised invoice.</flux:text>
        </div>

        <flux:separator />

        {{-- REQUEST --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Request</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">What's being corrected and why.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="correction_request_type" variant="listbox" searchable clearable label="Correction Type" placeholder="What to correct…" autofocus>
                        @foreach ($IC::requestTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="correction_reason" variant="listbox" clearable label="Correction Reason" placeholder="Why…">
                        @foreach ($IC::correctionReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="billing_action" variant="listbox" clearable label="Billing Action" placeholder="How to apply…">
                        @foreach ($IC::billingActions() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="invoice_type" variant="listbox" clearable label="Invoice Type" placeholder="Regular / Insurance…">
                        @foreach ($IC::invoiceTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="priority" variant="listbox" label="Priority" required>
                        @foreach ($IC::priorities() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input wire:model="invoice_reference" label="Invoice / Print Ref" placeholder="Invoice no / preview ref" class:input="font-mono" />
                    <flux:select wire:model="advisor_id" variant="listbox" searchable clearable label="Advisor" placeholder="Requested by…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="ad-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="mistake_by_id" variant="listbox" searchable clearable label="Mistake By" placeholder="Who erred…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="mb-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- CONTEXT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Context</flux:heading>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="JC…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                        @foreach ($this->jobCards as $jc)<flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="customer_id" variant="listbox" searchable clearable :filter="false" label="Customer" placeholder="Customer…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="customerSearch" placeholder="Search customer…" /></x-slot>
                        @foreach ($this->customers as $c)<flux:select.option :value="$c->id" wire:key="cu-{{ $c->id }}">{{ trim($c->first_name.' '.$c->last_name) }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="customer_vehicle_id" variant="listbox" searchable clearable :filter="false" label="Vehicle" placeholder="Registration…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Search vehicle…" /></x-slot>
                        @foreach ($this->vehicles as $vh)<flux:select.option :value="$vh->id" wire:key="vh-{{ $vh->id }}">{{ $vh->registration_no }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="insurance_company_id" variant="listbox" searchable clearable label="Insurance Company" placeholder="If insurance…">
                        @foreach ($this->insuranceCompanies as $ic)<flux:select.option :value="$ic->id" wire:key="in-{{ $ic->id }}">{{ $ic->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="workshop_department_id" variant="listbox" clearable label="Department" placeholder="Dept…">
                        @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="service_type_id" variant="listbox" clearable label="Service Type" placeholder="Type…">
                        @foreach ($this->serviceTypes as $st)<flux:select.option :value="$st->id" wire:key="st-{{ $st->id }}">{{ $st->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- CORRECTION LINES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Correction Lines</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Each line records an old vs new value (optional for header-only corrections).</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add line</flux:button>
                </div>

                @foreach ($items as $i => $item)
                    <div wire:key="item-{{ $i }}" class="space-y-2 p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <div class="grid grid-cols-1 md:grid-cols-[140px_1fr_auto] gap-2 items-end">
                            <flux:select wire:model.live="items.{{ $i }}.item_type" variant="listbox" size="sm" label="Type">
                                @foreach ($ITEM::itemTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model.live="items.{{ $i }}.spare_id" variant="listbox" size="sm" searchable clearable :filter="false" label="Spare (if part)" placeholder="Pick from catalogue…">
                                <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.spareSearch" placeholder="Search…" /></x-slot>
                                @foreach ($this->spareOptions($i) as $sp)
                                    <flux:select.option :value="$sp->id" wire:key="sp-{{ $i }}-{{ $sp->id }}">{{ $sp->name }} <span class="text-zinc-400">({{ $sp->spare_code }})</span></flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:button type="button" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" />
                        </div>

                        <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="What is being corrected (required)" required />
                        <flux:error name="items.{{ $i }}.description" />

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <flux:input wire:model="items.{{ $i }}.old_value" size="sm" label="Old Value" placeholder="Before" />
                            <flux:input wire:model="items.{{ $i }}.new_value" size="sm" label="New Value" placeholder="After" />
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <flux:select wire:model="items.{{ $i }}.uom_id" variant="listbox" size="sm" clearable label="UOM" placeholder="Unit…">
                                @foreach ($this->uoms as $u)<flux:select.option :value="$u->id" wire:key="uom-{{ $i }}-{{ $u->id }}">{{ $u->code ?? $u->name }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.tax_id" variant="listbox" size="sm" clearable label="Tax" placeholder="GST…">
                                @foreach ($this->taxes as $tx)<flux:select.option :value="$tx->id" wire:key="tx-{{ $i }}-{{ $tx->id }}">{{ rtrim(rtrim(number_format((float) $tx->gst_percent, 2), '0'), '.') }}%</flux:select.option>@endforeach
                            </flux:select>
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0" size="sm" label="Qty" class:input="text-right font-mono" />
                            <flux:input.group label="Rate">
                                <flux:input.group.prefix>₹</flux:input.group.prefix>
                                <flux:input wire:model="items.{{ $i }}.rate" type="number" step="0.01" min="0" size="sm" class:input="text-right font-mono" />
                            </flux:input.group>
                        </div>
                        <flux:input wire:model="items.{{ $i }}.other_note" size="sm" label="Other" placeholder="Any other correction detail" />
                    </div>
                @endforeach
            </div>
        </section>

        <flux:separator />

        {{-- STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Approval</flux:heading>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="status" variant="listbox" label="Correction Status" required>
                        @foreach ($IC::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <div x-show="$wire.status === 'rejected'" x-cloak>
                        <flux:select wire:model="rejection_reason" variant="listbox" clearable label="Rejection Reason" placeholder="Why rejected…">
                            @foreach ($IC::rejectionReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:error name="rejection_reason" />
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
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Customer request / GST certificate / original / revised invoice copy.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Correction remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('invoice-correction.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create request' }}</flux:button>
        </div>
    </form>
</div>
