@php($OLCN = \App\Modules\OutsideLabourCreditNote\Models\OutsideLabourCreditNote::class)
@php($ATT = \App\Modules\OutsideLabourCreditNote\Models\OutsideLabourCreditNoteAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('outside-labour-credit-note.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Outside Labour Credit / Debit Note
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($note_no ?: 'Edit Note') : 'New Credit / Debit Note' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Settle an outside-labour adjustment commercially.</flux:text>
        </div>

        <flux:separator />

        {{-- NOTE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Note</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Type, vendor and source references.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="note_type" variant="listbox" label="Note Type" required autofocus>
                        @foreach ($OLCN::noteTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="invoice_type" variant="listbox" clearable label="Invoice Type" placeholder="E-CN / Tax CN…">
                        @foreach ($OLCN::invoiceTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="service_specialist_id" variant="listbox" clearable label="Service Category" placeholder="Denting / Painting…">
                        @foreach ($this->serviceCategories as $sc)<flux:select.option :value="$sc->id" wire:key="sc-{{ $sc->id }}">{{ $sc->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="vendor_id" variant="listbox" searchable clearable :filter="false" label="Vendor / Contractor" placeholder="Supplier…" required>
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Search vendor…" /></x-slot>
                        @foreach ($this->vendors as $v)<flux:select.option :value="$v->id" wire:key="vn-{{ $v->id }}">{{ $v->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="transport_company_id" variant="listbox" searchable clearable :filter="false" label="Logistics / Transport" placeholder="Transport…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="transportSearch" placeholder="Search vendor…" /></x-slot>
                        @foreach ($this->transportCompanies as $t)<flux:select.option :value="$t->id" wire:key="tp-{{ $t->id }}">{{ $t->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="outside_labour_return_id" variant="listbox" searchable clearable :filter="false" label="Warranty / Return Ref" placeholder="OLRR…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="returnSearch" placeholder="Search return…" /></x-slot>
                        @foreach ($this->returns as $rt)<flux:select.option :value="$rt->id" wire:key="rt-{{ $rt->id }}">{{ $rt->return_no }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="JC…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                        @foreach ($this->jobCards as $jc)<flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="customer_vehicle_id" variant="listbox" searchable clearable :filter="false" label="Vehicle" placeholder="Registration…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Search vehicle…" /></x-slot>
                        @foreach ($this->vehicles as $vh)<flux:select.option :value="$vh->id" wire:key="vh-{{ $vh->id }}">{{ $vh->registration_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="workshop_department_id" variant="listbox" clearable label="Department" placeholder="Dept…">
                        @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="advisor_id" variant="listbox" searchable clearable label="Service Advisor" placeholder="Advisor…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="ad-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- LINES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Labour Lines</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Each line is an adjusted labour item, against an OL bill.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add line</flux:button>
                </div>

                @foreach ($items as $i => $item)
                    <div wire:key="item-{{ $i }}" class="space-y-2 p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-end">
                            <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Labour description (required)" required />
                            <flux:button type="button" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" />
                        </div>
                        <flux:error name="items.{{ $i }}.description" />

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <flux:select wire:model="items.{{ $i }}.outside_labour_bill_id" variant="listbox" size="sm" searchable clearable :filter="false" label="OL Bill Ref" placeholder="Bill…">
                                <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.billSearch" placeholder="Search bill…" /></x-slot>
                                @foreach ($this->billOptions($i) as $b)
                                    <flux:select.option :value="$b->id" wire:key="bl-{{ $i }}-{{ $b->id }}">{{ $b->bill_no }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.uom_id" variant="listbox" size="sm" clearable label="UOM" placeholder="Unit…">
                                @foreach ($this->uoms as $u)<flux:select.option :value="$u->id" wire:key="uom-{{ $i }}-{{ $u->id }}">{{ $u->code ?? $u->name }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.tax_id" variant="listbox" size="sm" clearable label="Tax" placeholder="GST…">
                                @foreach ($this->taxes as $tx)<flux:select.option :value="$tx->id" wire:key="tx-{{ $i }}-{{ $tx->id }}">{{ rtrim(rtrim(number_format((float) $tx->gst_percent, 2), '0'), '.') }}%</flux:select.option>@endforeach
                            </flux:select>
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0.01" size="sm" label="Qty" class:input="text-right font-mono" required />
                        </div>
                        <flux:input.group label="Rate" class="md:max-w-xs">
                            <flux:input.group.prefix>₹</flux:input.group.prefix>
                            <flux:input wire:model="items.{{ $i }}.rate" type="number" step="0.01" min="0" size="sm" class:input="text-right font-mono" />
                        </flux:input.group>
                    </div>
                @endforeach
            </div>
        </section>

        <flux:separator />

        {{-- SETTLEMENT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Settlement</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Reason, warranty and note status.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="return_reason" variant="listbox" clearable label="Return Reason" placeholder="Why adjusting…">
                        @foreach ($OLCN::returnReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="commercial_settlement" variant="listbox" clearable label="Commercial Settlement" placeholder="Partial / Full">
                        @foreach ($OLCN::commercialSettlements() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input.group label="Note Amount">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model="amount" type="number" step="0.01" min="0" class:input="text-right font-mono" />
                    </flux:input.group>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <flux:select wire:model="warranty_type" variant="listbox" clearable label="Warranty Type" placeholder="Vendor / Mfr…">
                        @foreach ($OLCN::warrantyTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="warranty_period" variant="listbox" clearable label="Warranty Period" placeholder="3 / 6 / 12 / 24 M">
                        @foreach ($OLCN::warrantyPeriods() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="status" variant="listbox" label="Note Status" required>
                        @foreach ($OLCN::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="vendor_rating_type" variant="listbox" clearable label="Vendor Rating On" placeholder="Quality / Price…">
                        @foreach ($OLCN::vendorRatingTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
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
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Vendor bill copy / warranty card.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Settlement remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('outside-labour-credit-note.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Post note' }}</flux:button>
        </div>
    </form>
</div>
