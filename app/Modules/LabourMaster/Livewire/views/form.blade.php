<div>
    <flux:modal name="labour-master-form" class="md:w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Labour' : 'New Labour' }}</flux:heading>
                <flux:subheading>Labour catalog used by estimates, proformas, and invoices.</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-[1fr_180px] gap-3">
                    <flux:input
                        wire:model="name"
                        label="Labour Name"
                        placeholder="GENERAL SERVICE"
                        required
                        autofocus
                    />
                    <flux:input
                        wire:model="labour_code"
                        label="Code"
                        placeholder="LB-00001"
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                <flux:textarea
                    wire:model="description"
                    label="Description"
                    placeholder="What this labour covers — e.g. 'Engine oil change including filter, sump cleaning'"
                    rows="2"
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input
                        wire:model="hsn_sac_code"
                        label="HSN / SAC Code"
                        placeholder="9988"
                        description="6-digit SAC for service-tax classification."
                        class:input="font-mono"
                    />
                    <flux:select wire:model="vehicle_segment_id" label="Vehicle Segment" variant="combobox" clearable>
                        <x-slot name="input">
                            <flux:select.input wire:model="vehicleSegmentSearch" placeholder="Pick or type to add…" />
                        </x-slot>
                        @foreach ($this->segments as $s)
                            <flux:select.option :value="$s->id" wire:key="seg-{{ $s->id }}">{{ $s->name }}</flux:select.option>
                        @endforeach
                        @can('vehicle_segment_master.create')
                            <flux:select.option.create wire:click="createSegment" min-length="2">
                                Create "<span wire:text="vehicleSegmentSearch"></span>"
                            </flux:select.option.create>
                        @endcan
                    </flux:select>
                </div>

                <flux:separator variant="subtle" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input.group label="Rate (Before Tax)">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input
                            wire:model.live.debounce.300ms="rate_before_tax"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class:input="text-right font-mono"
                        />
                    </flux:input.group>

                    <flux:select wire:model.live="tax_id" variant="listbox" searchable clearable label="Tax" placeholder="Pick a tax slab…">
                        @foreach ($this->taxes as $t)
                            <flux:select.option :value="$t->id" wire:key="tax-{{ $t->id }}">{{ $t->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                @if ($tax_id)
                    <div class="rounded-md border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/40 px-3 py-2 text-sm">
                        <span class="text-zinc-500">Rate including tax:</span>
                        <span class="font-mono font-medium">₹ {{ number_format($this->rateInclTax, 2) }}</span>
                    </div>
                @endif

                <flux:separator variant="subtle" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="workshop_department_id" variant="listbox" searchable clearable label="Department" placeholder="Pick a department…">
                        @foreach ($this->workshopDepartments as $d)
                            <flux:select.option :value="$d->id" wire:key="wd-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div class="grid grid-cols-2 gap-3">
                        <flux:select wire:model.live="inventory_group_id" variant="listbox" searchable clearable label="Inv. Group" placeholder="Group">
                            @foreach ($this->parentInventoryGroups as $g)
                                <flux:select.option :value="$g->id" wire:key="grp-{{ $g->id }}">{{ $g->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model="inventory_sub_group_id" variant="listbox" searchable clearable label="Sub-Group" :placeholder="$inventory_group_id ? 'Sub' : 'Pick group'" :disabled="! $inventory_group_id">
                            @foreach ($this->inventorySubGroups as $g)
                                <flux:select.option :value="$g->id" wire:key="subgrp-{{ $g->id }}">{{ $g->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <flux:textarea wire:model="remark" label="Remark" placeholder="Internal notes about this labour." rows="2" />

                <flux:separator variant="subtle" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:switch wire:model="is_osl" label="Outside Labour (OSL)" description="Toggle on if this labour is performed by an outside vendor, not in-house." />
                    <flux:switch wire:model="is_active" label="Active" description="Inactive labours are hidden from estimate forms but kept for history." />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
