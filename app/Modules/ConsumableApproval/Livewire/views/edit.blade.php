@php($CA = \App\Modules\ConsumableApproval\Models\ConsumableApproval::class)
@php($ITEM = \App\Modules\ConsumableApproval\Models\ConsumableApprovalItem::class)
@php($ATT = \App\Modules\ConsumableApproval\Models\ConsumableApprovalAttachment::class)
<div>
    <form wire:submit="save" novalidate class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('consumable-approval.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Consumable Approval
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($request_no ?: 'Edit Request') : 'New Consumable Approval' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Request approval to book a consumable / labour loss against a job card.</flux:text>
        </div>

        <flux:separator />

        {{-- REQUEST --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Request</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Job card, category and who approves.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="JC…" autofocus>
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                        @foreach ($this->jobCards as $jc)<flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="consumable_category" variant="listbox" clearable label="Consumable Category" placeholder="Paint / VA / Workshop…">
                        @foreach ($CA::consumableCategories() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="workshop_department_id" variant="listbox" clearable label="Department" placeholder="Dept…">
                        @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="approval_authority_id" variant="listbox" searchable clearable label="Approval Authority" placeholder="Store Mgr / Owner…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="aa-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="priority" variant="listbox" label="Priority" required>
                        @foreach ($CA::priorities() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="follow_up_mode_id" variant="listbox" clearable label="Follow-up Mode" placeholder="Call / Email…">
                        @foreach ($this->followUpModes as $fm)<flux:select.option :value="$fm->id" wire:key="fm-{{ $fm->id }}">{{ $fm->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ITEMS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Loss Lines</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Each line is a consumable / labour loss, with its loss-damage type and photo.</flux:text>
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
                            <flux:select wire:model.live="items.{{ $i }}.spare_id" variant="listbox" size="sm" searchable clearable :filter="false" label="Spare / Consumable" placeholder="Pick from catalogue…">
                                <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.spareSearch" placeholder="Search…" /></x-slot>
                                @foreach ($this->spareOptions($i) as $sp)
                                    <flux:select.option :value="$sp->id" wire:key="sp-{{ $i }}-{{ $sp->id }}">{{ $sp->name }} <span class="text-zinc-400">({{ $sp->spare_code }})</span></flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:button type="button" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" />
                        </div>

                        <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Description (required)" required />
                        <flux:error name="items.{{ $i }}.description" />

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <flux:select wire:model="items.{{ $i }}.loss_damage_type" variant="listbox" size="sm" clearable label="Loss / Damage Type" placeholder="Reason…">
                                @foreach ($CA::lossDamageTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
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
                            <flux:select wire:model="items.{{ $i }}.workshop_department_id" variant="listbox" size="sm" clearable label="Department" placeholder="Dept…">
                                @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="idp-{{ $i }}-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.tax_id" variant="listbox" size="sm" clearable label="Tax" placeholder="GST…">
                                @foreach ($this->taxes as $tx)<flux:select.option :value="$tx->id" wire:key="tx-{{ $i }}-{{ $tx->id }}">{{ rtrim(rtrim(number_format((float) $tx->gst_percent, 2), '0'), '.') }}%</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.vendor_purchase_order_id" variant="listbox" size="sm" searchable clearable :filter="false" label="Purchase Ref" placeholder="PO…">
                                <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.poSearch" placeholder="Search PO…" /></x-slot>
                                @foreach ($this->poOptions($i) as $po)<flux:select.option :value="$po->id" wire:key="po-{{ $i }}-{{ $po->id }}">{{ $po->po_no }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.outside_labour_order_id" variant="listbox" size="sm" searchable clearable :filter="false" label="Outside Labour Ref" placeholder="OLO…">
                                <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.olSearch" placeholder="Search order…" /></x-slot>
                                @foreach ($this->olOptions($i) as $o)<flux:select.option :value="$o->id" wire:key="ol-{{ $i }}-{{ $o->id }}">{{ $o->order_no }}</flux:select.option>@endforeach
                            </flux:select>
                        </div>

                        <div>
                            <flux:input type="file" wire:model="photoFiles.{{ $i }}" size="sm" label="Damaged Photo" accept=".jpg,.jpeg,.png,.webp" class="md:max-w-sm" />
                            @if (! empty($item['damaged_photo_path']))<flux:text size="sm" class="text-zinc-500 mt-1">Uploaded ✓</flux:text>@endif
                            <flux:error name="photoFiles.{{ $i }}" />
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
                <flux:text size="sm" class="mt-1 text-zinc-500">Status and response.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="status" variant="listbox" label="Approval Status" required>
                        @foreach ($CA::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="approval_response" variant="listbox" clearable label="Approval Response" placeholder="Justification / Other…">
                        @foreach ($CA::approvalResponses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>

                {{-- Approval screenshot --}}
                <div class="pt-2">
                    <div class="flex items-center justify-between mb-2">
                        <flux:text size="sm" class="font-medium">Approval Screenshot</flux:text>
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addAttachment">Add file</flux:button>
                    </div>
                    @forelse ($attachments as $i => $att)
                        <div wire:key="att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-end mb-2 p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                            <div>
                                <flux:input type="file" wire:model="attachmentFiles.{{ $i }}" size="sm" label="File" accept=".jpg,.jpeg,.png,.webp,.pdf" />
                                @if (! empty($att['path']))<flux:text size="sm" class="text-zinc-500 mt-1">Current: {{ $att['original_name'] ?? basename($att['path']) }}</flux:text>@endif
                                <flux:error name="attachmentFiles.{{ $i }}" />
                            </div>
                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeAttachment({{ $i }})" class="h-9!" />
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Attach the approval screenshot / chat.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Justification / remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('consumable-approval.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create request' }}</flux:button>
        </div>
    </form>
</div>
