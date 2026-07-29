@php($OLR = \App\Modules\GoodsReturnNote\Models\GoodsReturnNote::class)
@php($ITEM = \App\Modules\GoodsReturnNote\Models\GoodsReturnNoteItem::class)
@php($ATT = \App\Modules\GoodsReturnNote\Models\GoodsReturnNoteAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('goods-return-note.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Goods Return Note
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($return_no ?: 'Edit Return') : 'New Goods Return Note' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Ask an outside vendor to resolve a job under warranty — parts and/or labour in one claim.</flux:text>
        </div>

        <flux:separator />

        {{-- CLAIM --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Claim</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Type, vendor and the vehicle it's for.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="return_type" variant="listbox" label="Return Type" required autofocus>
                        @foreach ($OLR::returnTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="claim_type" variant="listbox" clearable label="Claim Type" placeholder="Labour / Parts…">
                        @foreach ($OLR::claimTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="return_reason" variant="listbox" clearable label="Return Reason" placeholder="Why returning…">
                        @foreach ($OLR::returnReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="vendor_id" variant="listbox" searchable clearable :filter="false" label="Vendor / Contractor" placeholder="Who did the work…" required>
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Search vendor…" /></x-slot>
                        @foreach ($this->vendors as $v)<flux:select.option :value="$v->id" wire:key="vn-{{ $v->id }}">{{ $v->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="transport_company_id" variant="listbox" searchable clearable :filter="false" label="Transport / Courier" placeholder="Logistics…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="transportSearch" placeholder="Search vendor…" /></x-slot>
                        @foreach ($this->transportCompanies as $t)<flux:select.option :value="$t->id" wire:key="tp-{{ $t->id }}">{{ $t->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="regular_sales_invoice_id" variant="listbox" searchable clearable :filter="false" label="Sales Invoice" placeholder="Original invoice…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="invoiceSearch" placeholder="Search invoice…" /></x-slot>
                        @foreach ($this->invoices as $iv)<flux:select.option :value="$iv->id" wire:key="iv-{{ $iv->id }}">{{ $iv->invoice_no }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="customer_id" variant="listbox" searchable clearable :filter="false" label="Customer" placeholder="Customer…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="customerSearch" placeholder="Search customer…" /></x-slot>
                        @foreach ($this->customers as $c)<flux:select.option :value="$c->id" wire:key="cu-{{ $c->id }}">{{ trim($c->first_name.' '.$c->last_name) }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="customer_vehicle_id" variant="listbox" searchable clearable :filter="false" label="Vehicle" placeholder="Registration…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Search vehicle…" /></x-slot>
                        @foreach ($this->vehicles as $vh)<flux:select.option :value="$vh->id" wire:key="vh-{{ $vh->id }}">{{ $vh->registration_no }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <flux:select wire:model="workshop_department_id" variant="listbox" clearable label="Department" placeholder="Dept…">
                        @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="advisor_id" variant="listbox" searchable clearable label="Advisor" placeholder="Advisor…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="ad-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="technician_id" variant="listbox" searchable clearable label="Technician" placeholder="Technician…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="tc-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="store_incharge_id" variant="listbox" searchable clearable label="Store In-charge" placeholder="Store…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="si-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ITEMS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Return Lines</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Each line is a part or labour item, with before/after photo evidence.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add line</flux:button>
                </div>

                @foreach ($items as $i => $item)
                    <div wire:key="item-{{ $i }}" class="space-y-2 p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <div class="grid grid-cols-1 md:grid-cols-[140px_1fr_auto] gap-2 items-end">
                            <flux:select wire:model.live="items.{{ $i }}.item_type" variant="listbox" size="sm" label="Item Type">
                                @foreach ($ITEM::itemTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model.live="items.{{ $i }}.spare_id" variant="listbox" size="sm" searchable clearable :filter="false" label="Spare (if part)" placeholder="Pick from catalogue…">
                                <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.spareSearch" placeholder="Search spare / part no…" /></x-slot>
                                @foreach ($this->spareOptions($i) as $sp)
                                    <flux:select.option :value="$sp->id" wire:key="sp-{{ $i }}-{{ $sp->id }}">{{ $sp->name }} <span class="text-zinc-400">({{ $sp->spare_code }})</span></flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:button type="button" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" />
                        </div>

                        <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Description (required)" required />
                        <flux:error name="items.{{ $i }}.description" />

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <flux:select wire:model="items.{{ $i }}.outside_labour_bill_id" variant="listbox" size="sm" searchable clearable :filter="false" label="OL Bill Ref" placeholder="Bill…">
                                <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.billSearch" placeholder="Search bill…" /></x-slot>
                                @foreach ($this->billOptions($i) as $b)
                                    <flux:select.option :value="$b->id" wire:key="bl-{{ $i }}-{{ $b->id }}">{{ $b->bill_no }}</flux:select.option>
                                @endforeach
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
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0.01" size="sm" label="Qty" class:input="text-right font-mono" required />
                            <flux:input.group label="Rate">
                                <flux:input.group.prefix>₹</flux:input.group.prefix>
                                <flux:input wire:model="items.{{ $i }}.rate" type="number" step="0.01" min="0" size="sm" class:input="text-right font-mono" />
                            </flux:input.group>
                            <flux:select wire:model="items.{{ $i }}.material_condition" variant="listbox" size="sm" clearable label="Material Condition" placeholder="New / Used…">
                                @foreach ($ITEM::materialConditions() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                            </flux:select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <div>
                                <flux:input type="file" wire:model="beforeFiles.{{ $i }}" size="sm" label="Before Work Photo" accept=".jpg,.jpeg,.png,.webp" />
                                @if (! empty($item['before_photo_path']))<flux:text size="sm" class="text-zinc-500 mt-1">Uploaded ✓</flux:text>@endif
                                <flux:error name="beforeFiles.{{ $i }}" />
                            </div>
                            <div>
                                <flux:input type="file" wire:model="afterFiles.{{ $i }}" size="sm" label="After Work Photo" accept=".jpg,.jpeg,.png,.webp" />
                                @if (! empty($item['after_photo_path']))<flux:text size="sm" class="text-zinc-500 mt-1">Uploaded ✓</flux:text>@endif
                                <flux:error name="afterFiles.{{ $i }}" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <flux:separator />

        {{-- WARRANTY & RESOLUTION --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Warranty & Resolution</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Warranty, rework, status and settlement.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="warranty_type" variant="listbox" clearable label="Warranty Type" placeholder="Within / Expired…">
                        @foreach ($OLR::warrantyTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="warranty_period" variant="listbox" clearable label="Warranty Period" placeholder="1 / 3 / 6 / 12 Months">
                        @foreach ($OLR::warrantyPeriods() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="rework_type" variant="listbox" clearable label="Rework Type" placeholder="Refit / Repaint…">
                        @foreach ($OLR::reworkTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <flux:select wire:model.live="tat_option" variant="listbox" clearable label="Turnaround (TAT)" placeholder="1 / 2 / 3 Days…">
                        @foreach ($OLR::tatOptions() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <div x-show="$wire.tat_option === 'custom'" x-cloak>
                        <flux:input wire:model="tat_custom_days" type="number" min="1" max="365" label="Custom TAT (days)" class:input="font-mono" />
                        <flux:error name="tat_custom_days" />
                    </div>
                    <flux:input.group label="Recovery Amount">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model="recovery_amount" type="number" step="0.01" min="0" class:input="text-right font-mono" />
                    </flux:input.group>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="status" variant="listbox" label="Claim Status" required>
                        @foreach ($OLR::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="priority_id" variant="listbox" clearable label="Priority" placeholder="Normal / High…">
                        @foreach ($this->priorities as $p)<flux:select.option :value="$p->id" wire:key="pr-{{ $p->id }}">{{ $p->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div x-show="$wire.status === 'counter_proposal'" x-cloak>
                    <flux:select wire:model="counter_proposal" variant="listbox" clearable label="Counter Proposal" placeholder="Free rework / Shared cost…" class="md:max-w-sm">
                        @foreach ($OLR::counterProposals() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:error name="counter_proposal" />
                </div>
                <div x-show="$wire.status === 'rejected'" x-cloak>
                    <flux:select wire:model="rejection_reason" variant="listbox" clearable label="Rejection Reason" placeholder="Why rejected…" class="md:max-w-sm">
                        @foreach ($OLR::rejectionReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:error name="rejection_reason" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <flux:select wire:model="follow_up_mode_id" variant="listbox" clearable label="Follow-up Mode" placeholder="Call / Email…">
                        @foreach ($this->followUpModes as $fm)<flux:select.option :value="$fm->id" wire:key="fm-{{ $fm->id }}">{{ $fm->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model.live="reminder_frequency" variant="listbox" clearable label="Reminder Frequency" placeholder="Config only — never sent">
                        @foreach ($OLR::reminderFrequencies() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <div x-show="$wire.reminder_frequency === 'custom'" x-cloak>
                        <flux:input wire:model="reminder_custom_days" type="number" min="1" max="90" label="Every N days" class:input="font-mono" />
                        <flux:error name="reminder_custom_days" />
                    </div>
                </div>
                <flux:select wire:model="vendor_rating_type" variant="listbox" clearable label="Vendor Rating On" placeholder="Quality / Price…" class="md:max-w-sm">
                    @foreach ($OLR::vendorRatingTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                </flux:select>
            </div>
        </section>

        <flux:separator />

        {{-- ATTACHMENTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Attachments</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Vendor bill, warranty card, complaint / damage photos, inspection report, additional views.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addAttachment">Add file</flux:button>
                </div>

                @forelse ($attachments as $i => $att)
                    <div wire:key="att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2 items-end p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <flux:select wire:model="attachments.{{ $i }}.attachment_type" variant="listbox" size="sm" clearable label="Type" placeholder="Document type…">
                            @foreach ($ATT::attachmentTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
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

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Claim remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('goods-return-note.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create claim' }}</flux:button>
        </div>
    </form>
</div>
