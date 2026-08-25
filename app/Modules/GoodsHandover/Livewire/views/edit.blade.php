@php($GHO = \App\Modules\GoodsHandover\Models\GoodsHandover::class)
@php($ITEM = \App\Modules\GoodsHandover\Models\GoodsHandoverItem::class)
@php($ATT = \App\Modules\GoodsHandover\Models\GoodsHandoverAttachment::class)
<div>
    <form wire:submit="save" novalidate class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('goods-handover.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Goods Handover / Parts Return
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($handover_no ?: 'Edit Handover') : 'New Goods Handover' }}</flux:heading>
            @if ($sourceGrnNo)
                <flux:badge color="sky" size="sm" icon="arrow-right-circle" class="mt-2">Carried forward from {{ $sourceGrnNo }}</flux:badge>
            @endif
            <flux:text size="sm" class="mt-1 text-zinc-500">Hand parts to a technician and capture any returns.</flux:text>
        </div>

        <flux:separator />

        {{-- HANDOVER --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Handover</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Who hands over, who receives, against which order.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <flux:select wire:model="workshop_department_id" variant="listbox" clearable label="Department" placeholder="Dept…">
                        @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="handover_by_id" variant="listbox" searchable clearable label="Handover By (Store)" placeholder="Store exec…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="hb-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="received_by_id" variant="listbox" searchable clearable label="Received By (Tech)" placeholder="Technician…" required>
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="rb-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="verified_by_id" variant="listbox" searchable clearable label="Verified By (Floor)" placeholder="Advisor…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="vb-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <flux:select wire:model="goods_receipt_id" variant="listbox" searchable clearable :filter="false" label="Goods Receipt" placeholder="GRN…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="grnSearch" placeholder="Search GRN…" /></x-slot>
                        @foreach ($this->goodsReceipts as $g)<flux:select.option :value="$g->id" wire:key="grn-{{ $g->id }}">{{ $g->grn_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="final_work_order_id" variant="listbox" searchable clearable :filter="false" label="Work Order (FWO)" placeholder="FWO…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="fwoSearch" placeholder="Search FWO…" /></x-slot>
                        @foreach ($this->workOrders as $w)<flux:select.option :value="$w->id" wire:key="fwo-{{ $w->id }}">{{ $w->order_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model.live="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="JC…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                        @foreach ($this->jobCards as $jc)<flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="vendor_id" variant="listbox" searchable clearable :filter="false" label="Service Contractor" placeholder="If outsourced…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Search vendor…" /></x-slot>
                        @foreach ($this->vendors as $v)<flux:select.option :value="$v->id" wire:key="vn-{{ $v->id }}">{{ $v->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ITEMS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Parts</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Issued qty, returned qty, condition and verification per line.</flux:text>
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
                            <flux:select wire:model="items.{{ $i }}.spare_brand_id" variant="listbox" size="sm" searchable clearable label="Brand" placeholder="Brand…">
                                @foreach ($this->spareBrands as $b)<flux:select.option :value="$b->id" wire:key="sb-{{ $i }}-{{ $b->id }}">{{ $b->name }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.uom_id" variant="listbox" size="sm" clearable label="UOM" placeholder="Unit…">
                                @foreach ($this->uoms as $u)<flux:select.option :value="$u->id" wire:key="uom-{{ $i }}-{{ $u->id }}">{{ $u->code ?? $u->name }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0.01" size="sm" label="Issued Qty" class:input="text-right font-mono" required />
                            <flux:input wire:model="items.{{ $i }}.returned_quantity" type="number" step="0.01" min="0" size="sm" label="Returned Qty" class:input="text-right font-mono" />
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <flux:select wire:model="items.{{ $i }}.customer_vehicle_id" variant="listbox" size="sm" searchable clearable :filter="false" label="Vehicle" placeholder="Reg…">
                                <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.vehicleSearch" placeholder="Search vehicle…" /></x-slot>
                                @foreach ($this->vehicleOptions($i) as $vh)<flux:select.option :value="$vh->id" wire:key="vh-{{ $i }}-{{ $vh->id }}">{{ $vh->registration_no }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.material_condition" variant="listbox" size="sm" clearable label="Material Condition" placeholder="New / Used…">
                                @foreach ($ITEM::materialConditions() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.physical_verification" variant="listbox" size="sm" clearable label="Physical Verification" placeholder="Excess / Damage…">
                                @foreach ($ITEM::physicalVerifications() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.damage_type" variant="listbox" size="sm" clearable label="Damage Type" placeholder="If damaged…">
                                @foreach ($ITEM::damageTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                            </flux:select>
                        </div>

                        <div>
                            <flux:input type="file" wire:model="photoFiles.{{ $i }}" size="sm" label="Spare Photo" accept=".jpg,.jpeg,.png,.webp" class="md:max-w-sm" />
                            @if (! empty($item['photo_path']))<flux:text size="sm" class="text-zinc-500 mt-1">Uploaded ✓</flux:text>@endif
                            <flux:error name="photoFiles.{{ $i }}" />
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <flux:separator />

        {{-- RETURN & STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Return & Status</flux:heading>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model.live="material_return_status" variant="listbox" clearable label="Material Return Status" placeholder="Partial / Full…">
                        @foreach ($GHO::materialReturnStatuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <div x-show="$wire.material_return_status" x-cloak>
                        <flux:select wire:model="return_reason" variant="listbox" clearable label="Return Reason" placeholder="Why returned…">
                            @foreach ($GHO::returnReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:error name="return_reason" />
                    </div>
                    <flux:select wire:model="parts_return_by_id" variant="listbox" searchable clearable label="Parts Return By" placeholder="Technician / Advisor…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="pr-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <flux:select wire:model="status" variant="listbox" label="Handover Status" required class="md:max-w-sm">
                    @foreach ($GHO::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                </flux:select>

                {{-- Footer attachments --}}
                <div class="pt-2">
                    <div class="flex items-center justify-between mb-2">
                        <flux:text size="sm" class="font-medium">Additional Evidence</flux:text>
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
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Damage photo / fault evidence.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Handover / return remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('goods-handover.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Record handover' }}</flux:button>
        </div>
    </form>
</div>
