@php($SC = \App\Modules\StockCounting\Models\StockCount::class)
@php($ITEM = \App\Modules\StockCounting\Models\StockCountItem::class)
@php($ATT = \App\Modules\StockCounting\Models\StockCountAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('stock-counting.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Stock Counting
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($count_no ?: 'Edit Count') : 'New Stock Count' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Verify physical stock against system stock and record variances.</flux:text>
        </div>

        <flux:separator />

        {{-- SESSION --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Counting Session</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Scope, method and team.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="storage_location_id" variant="listbox" searchable clearable label="Storage Location" placeholder="Rack / bin…" autofocus>
                        @foreach ($this->storageLocations as $r)<flux:select.option :value="$r->id" wire:key="rk-{{ $r->id }}">{{ $r->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="inventory_group_id" variant="listbox" searchable clearable label="Inventory Group" placeholder="Group…">
                        @foreach ($this->inventoryGroups as $g)<flux:select.option :value="$g->id" wire:key="ig-{{ $g->id }}">{{ $g->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="counting_method" variant="listbox" clearable label="Counting Method" placeholder="Method…">
                        @foreach ($SC::countingMethods() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:date-picker wire:model="count_start_date" label="Start Date" with-today selectable-header fixed-weeks type="input" />
                    <div>
                        <flux:date-picker wire:model="count_end_date" label="End Date" with-today selectable-header fixed-weeks type="input" />
                        <flux:error name="count_end_date" />
                    </div>
                    <flux:select wire:model="verification_status" variant="listbox" label="Verification Status" required>
                        @foreach ($SC::verificationStatuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="team_leader_id" variant="listbox" searchable clearable label="Team Leader" placeholder="Employee…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="tl-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input wire:model="team_name" label="Team Name" placeholder="Team…" />
                    <flux:input wire:model="team_members" label="Team Members" placeholder="Names, comma separated" />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- COUNTED ITEMS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Counted Items</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">System vs physical stock; variance is computed on save.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add item</flux:button>
                </div>
                @foreach ($items as $i => $item)
                    <div wire:key="item-{{ $i }}" class="space-y-2 p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-end">
                            <flux:select wire:model.live="items.{{ $i }}.spare_id" variant="listbox" size="sm" searchable clearable :filter="false" label="Spare" placeholder="Pick from catalogue…">
                                <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="items.{{ $i }}.spareSearch" placeholder="Search…" /></x-slot>
                                @foreach ($this->spareOptions($i) as $sp)
                                    <flux:select.option :value="$sp->id" wire:key="sp-{{ $i }}-{{ $sp->id }}">{{ $sp->name }} <span class="text-zinc-400">({{ $sp->spare_code }})</span></flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:button type="button" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" />
                        </div>
                        <flux:error name="items.{{ $i }}.spare_id" />
                        <div class="grid grid-cols-1 md:grid-cols-[1fr_160px] gap-2">
                            <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Item description (required)" required />
                            <flux:input wire:model="items.{{ $i }}.barcode" size="sm" placeholder="Barcode" class:input="font-mono" />
                        </div>
                        <flux:error name="items.{{ $i }}.description" />
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <flux:input wire:model="items.{{ $i }}.system_stock" type="number" step="0.01" size="sm" label="System" class:input="text-right font-mono" required />
                            <flux:input wire:model="items.{{ $i }}.physical_stock" type="number" step="0.01" size="sm" label="Physical" class:input="text-right font-mono" required />
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0" size="sm" label="Count Qty" class:input="text-right font-mono" required />
                            <flux:select wire:model="items.{{ $i }}.uom_id" variant="listbox" size="sm" clearable label="UOM" placeholder="Unit…">
                                @foreach ($this->uoms as $u)<flux:select.option :value="$u->id" wire:key="uom-{{ $i }}-{{ $u->id }}">{{ $u->code ?? $u->name }}</flux:select.option>@endforeach
                            </flux:select>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <flux:select wire:model="items.{{ $i }}.mismatch_reason" variant="listbox" size="sm" clearable searchable label="Variance Reason" placeholder="If mismatched…">
                                @foreach ($ITEM::varianceReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.spares_condition" variant="listbox" size="sm" clearable label="Condition" placeholder="If damaged…">
                                @foreach ($ITEM::sparesConditions() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                            </flux:select>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            <flux:input wire:model="items.{{ $i }}.purchase_invoice_no" size="sm" label="Purchase Inv No" placeholder="Invoice…" />
                            <flux:input wire:model="items.{{ $i }}.vendor_name" size="sm" label="Vendor" placeholder="Vendor…" />
                            <flux:input wire:model="items.{{ $i }}.remark" size="sm" label="Remark" placeholder="Note…" />
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <flux:separator />

        {{-- ATTACHMENTS & NOTES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Attachments &amp; Notes</flux:heading>
            </div>
            <div class="space-y-4 min-w-0">
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
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Count sheet / management approval / damage photos.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Count session notes." />
                <flux:textarea wire:model="remarks" label="Remarks" rows="2" placeholder="Final remarks / summary." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('stock-counting.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create Count' }}</flux:button>
        </div>
    </form>
</div>
