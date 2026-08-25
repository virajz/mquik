@php($ESA = \App\Modules\ExcessStockApproval\Models\ExcessStockApproval::class)
@php($ATT = \App\Modules\ExcessStockApproval\Models\ExcessStockApprovalAttachment::class)
<div>
    <form wire:submit="save" novalidate class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('excess-stock-approval.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Excess Stock Approval
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($request_no ?: 'Edit Request') : 'New Excess Stock Approval' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Request approval to return or write off excess / dead stock.</flux:text>
        </div>

        <flux:separator />

        {{-- REQUEST --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Request</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Reason and source references.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model.live="excess_stock_reason" variant="listbox" clearable label="Excess Stock Reason" placeholder="Why excess…" autofocus>
                        @foreach ($ESA::excessStockReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <div x-show="$wire.excess_stock_reason === 'vendor_return_rejected'" x-cloak>
                        <flux:select wire:model="vendor_rejection_reason" variant="listbox" clearable label="Vendor Rejection Reason" placeholder="Why rejected…">
                            @foreach ($ESA::vendorRejectionReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:error name="vendor_rejection_reason" />
                    </div>
                    <flux:select wire:model="priority" variant="listbox" label="Priority" required>
                        @foreach ($ESA::priorities() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="goods_receipt_id" variant="listbox" searchable clearable :filter="false" label="GRN Reference" placeholder="GRN…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="grnSearch" placeholder="Search GRN…" /></x-slot>
                        @foreach ($this->goodsReceipts as $g)<flux:select.option :value="$g->id" wire:key="grn-{{ $g->id }}">{{ $g->grn_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="goods_handover_id" variant="listbox" searchable clearable :filter="false" label="Technician Parts Return" placeholder="Handover…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="handoverSearch" placeholder="Search handover…" /></x-slot>
                        @foreach ($this->handovers as $h)<flux:select.option :value="$h->id" wire:key="gho-{{ $h->id }}">{{ $h->handover_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input wire:model="purchase_invoice_reference" label="Purchase Invoice Ref" placeholder="Invoice no" class:input="font-mono" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="workshop_department_id" variant="listbox" clearable label="Department" placeholder="Dept…">
                        @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="service_type_id" variant="listbox" clearable label="Service Type" placeholder="Type…">
                        @foreach ($this->serviceTypes as $st)<flux:select.option :value="$st->id" wire:key="st-{{ $st->id }}">{{ $st->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="follow_up_mode_id" variant="listbox" clearable label="Follow-up Mode" placeholder="Call / Email…">
                        @foreach ($this->followUpModes as $fm)<flux:select.option :value="$fm->id" wire:key="fm-{{ $fm->id }}">{{ $fm->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <flux:select wire:model="advisor_id" variant="listbox" searchable clearable label="Service Advisor" placeholder="Advisor…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="ad-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="store_incharge_id" variant="listbox" searchable clearable label="Store In-charge" placeholder="Store…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="si-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="store_executive_id" variant="listbox" searchable clearable label="Store Executive" placeholder="Store exec…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="se-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="mistake_by_id" variant="listbox" searchable clearable label="Mistake By" placeholder="Who erred…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="mb-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ITEMS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Excess Stock Lines</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Each line is an excess spare — qty × rate is the excess value.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add part</flux:button>
                </div>

                @foreach ($items as $i => $item)
                    <div wire:key="item-{{ $i }}" class="space-y-2 p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
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
                            <flux:select wire:model="items.{{ $i }}.uom_id" variant="listbox" size="sm" clearable label="UOM" placeholder="Unit…">
                                @foreach ($this->uoms as $u)<flux:select.option :value="$u->id" wire:key="uom-{{ $i }}-{{ $u->id }}">{{ $u->code ?? $u->name }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.tax_id" variant="listbox" size="sm" clearable label="Tax" placeholder="GST…">
                                @foreach ($this->taxes as $tx)<flux:select.option :value="$tx->id" wire:key="tx-{{ $i }}-{{ $tx->id }}">{{ rtrim(rtrim(number_format((float) $tx->gst_percent, 2), '0'), '.') }}%</flux:select.option>@endforeach
                            </flux:select>
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0.01" size="sm" label="Qty" class:input="text-right font-mono" required />
                            <flux:input.group label="Rate">
                                <flux:input.group.prefix>₹</flux:input.group.prefix>
                                <flux:input wire:model="items.{{ $i }}.rate" type="number" step="0.01" min="0" size="sm" class:input="text-right font-mono" />
                            </flux:input.group>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <flux:separator />

        {{-- APPROVAL --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Approval</flux:heading>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:select wire:model="status" variant="listbox" label="Approval Status" required class="md:max-w-sm">
                    @foreach ($ESA::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                </flux:select>

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
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Purchase invoice / damaged proof / vendor rejection proof.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Approval remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('excess-stock-approval.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create request' }}</flux:button>
        </div>
    </form>
</div>
