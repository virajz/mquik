<div>
    <form wire:submit="save" class="max-w-4xl">
        {{-- Page header --}}
        <div class="mb-8">
            <flux:link :href="route('spare-master.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" />
                Spares
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">
                {{ $editingId ? $name : 'New Spare' }}
            </flux:heading>
        </div>

        <flux:separator />

        {{-- IDENTITY --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Identity</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Spare name, part number, and a short description.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-[1fr_180px] gap-4">
                    <flux:input
                        wire:model="name"
                        label="Spare Name"
                        placeholder="BRAKE PAD - FRONT"
                        required
                        autofocus
                    />
                    <flux:input
                        wire:model="spare_code"
                        label="Part No."
                        placeholder="SP-00001"
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                <flux:textarea
                    wire:model="description"
                    label="Description"
                    placeholder="Vehicle / fitment notes — e.g. 'Brake pad set front, Maruti Swift Petrol 2018+'"
                    rows="2"
                />
            </div>
        </section>

        <flux:separator />

        {{-- CLASSIFICATION --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Classification</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Brand, HSN tax code, unit of measure, and how it's barcoded.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="spare_brand_id" variant="combobox" label="Brand" clearable>
                        <x-slot name="input">
                            <flux:select.input wire:model="spareBrandSearch" placeholder="Pick or type to add…" />
                        </x-slot>
                        @foreach ($this->brands as $b)
                            <flux:select.option :value="$b->id" wire:key="brand-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                        @endforeach
                        @can('spare_brand_master.create')
                            <flux:select.option.create wire:click="createSpareBrand" min-length="2">
                                Create "<span wire:text="spareBrandSearch"></span>"
                            </flux:select.option.create>
                        @endcan
                    </flux:select>

                    <flux:input
                        wire:model="hsn_code"
                        label="HSN Code"
                        placeholder="8708 — 4 to 8 digits"
                        class:input="font-mono"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="uom_id" variant="combobox" label="Unit of Measure" clearable>
                        <x-slot name="input">
                            <flux:select.input wire:model="uomSearch" placeholder="Pick or type to add…" />
                        </x-slot>
                        @foreach ($this->uoms as $u)
                            <flux:select.option :value="$u->id" wire:key="uom-{{ $u->id }}">{{ $u->name }}{{ $u->code ? ' ('.$u->code.')' : '' }}</flux:select.option>
                        @endforeach
                        @can('unit_of_measure_master.create')
                            <flux:select.option.create wire:click="createUom" min-length="2">
                                Create "<span wire:text="uomSearch"></span>"
                            </flux:select.option.create>
                        @endcan
                    </flux:select>

                    <flux:select wire:model="barcode_type" variant="listbox" clearable label="Barcode Type" placeholder="None">
                        <flux:select.option value="EAN-13">EAN-13</flux:select.option>
                        <flux:select.option value="CODE-128">Code-128</flux:select.option>
                        <flux:select.option value="QR">QR Code</flux:select.option>
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- PRICING --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Pricing & Tax</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Sale rate before tax and the GST slab applied at billing.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

                    <flux:select
                        wire:model.live="tax_id"
                        variant="listbox"
                        searchable
                        clearable
                        label="Tax Slab"
                        placeholder="Pick a tax slab…"
                    >
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
            </div>
        </section>

        <flux:separator />

        {{-- INVENTORY PLACEMENT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Inventory Placement</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Where this spare lives — by department, inventory group/sub-group, and physical rack.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select
                        wire:model="workshop_department_id"
                        variant="listbox"
                        searchable
                        clearable
                        label="Department"
                        placeholder="Pick a department…"
                    >
                        @foreach ($this->workshopDepartments as $d)
                            <flux:select.option :value="$d->id" wire:key="wd-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input
                        wire:model="location"
                        label="Storage Location"
                        placeholder="RACK-A-12"
                        class:input="font-mono uppercase"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select
                        wire:model.live="inventory_group_id"
                        variant="listbox"
                        searchable
                        clearable
                        label="Inventory Group"
                        placeholder="e.g. Brakes"
                    >
                        @foreach ($this->parentInventoryGroups as $g)
                            <flux:select.option :value="$g->id" wire:key="grp-{{ $g->id }}">{{ $g->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select
                        wire:model="inventory_sub_group_id"
                        variant="listbox"
                        searchable
                        clearable
                        label="Sub-Group"
                        :placeholder="$inventory_group_id ? 'Pick a sub-group…' : 'Pick a group first'"
                        :disabled="! $inventory_group_id"
                    >
                        @foreach ($this->inventorySubGroups as $g)
                            <flux:select.option :value="$g->id" wire:key="subgrp-{{ $g->id }}">{{ $g->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select
                        wire:model="part_type_id"
                        variant="listbox"
                        searchable
                        clearable
                        label="Part Type"
                        placeholder="Genuine / After Market…"
                    >
                        @foreach ($this->partTypes as $pt)
                            <flux:select.option :value="$pt->id" wire:key="pt-{{ $pt->id }}">{{ $pt->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select
                        wire:model="rack_id"
                        variant="listbox"
                        searchable
                        clearable
                        label="Rack"
                        placeholder="Pick a rack…"
                    >
                        @foreach ($this->racks as $r)
                            <flux:select.option :value="$r->id" wire:key="rk-{{ $r->id }}">{{ $r->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:select
                    wire:model="vendor_ids"
                    variant="listbox"
                    multiple
                    searchable
                    clearable
                    label="Suppliers / Vendors"
                    placeholder="Vendors that supply this part…"
                >
                    @foreach ($this->vendorOptions as $v)
                        <flux:select.option :value="$v->id" wire:key="ven-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="vendor_ids" />
            </div>
        </section>

        <flux:separator />

        {{-- STOCK THRESHOLDS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Stock Thresholds</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Reorder when stock falls below Min. Don't carry more than Max.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        wire:model="min_qty"
                        type="number"
                        step="0.01"
                        min="0"
                        label="Min Qty"
                        placeholder="0"
                        class:input="text-right font-mono"
                    />
                    <flux:input
                        wire:model="max_qty"
                        type="number"
                        step="0.01"
                        min="0"
                        label="Max Qty"
                        placeholder="0"
                        class:input="text-right font-mono"
                    />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- TYRE --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Tyre Details</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Toggle on for tyre items to capture dimension, rim, LI-SI and tread pattern.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:switch
                    wire:model.live="is_tyre"
                    label="This is a tyre"
                />

                @if ($is_tyre)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input
                            wire:model="tyre_dimension"
                            label="Tyre Dimension"
                            placeholder="195/65 R15"
                            class:input="font-mono"
                            required
                        />
                        <flux:input
                            wire:model="rim_size"
                            label="Rim Size (inch)"
                            placeholder="15"
                            class:input="font-mono"
                            required
                        />
                        <flux:input
                            wire:model="load_speed_index"
                            label="Load / Speed Index"
                            placeholder="91H"
                            class:input="font-mono"
                        />
                        <flux:input
                            wire:model="tread_pattern"
                            label="Tread Pattern"
                            placeholder="HIGHWAY / OFF-ROAD / TOURING"
                            class:input="uppercase"
                        />
                    </div>
                @endif
            </div>
        </section>

        <flux:separator />

        {{-- VEHICLE COMPATIBILITY --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Vehicle Compatibility</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Variants this spare fits. Leave empty for universal items.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:select
                    wire:model="variant_ids"
                    variant="listbox"
                    multiple
                    searchable
                    placeholder="Pick one or more variants…"
                >
                    @foreach ($this->variants as $v)
                        <flux:select.option :value="$v['id']" wire:key="var-{{ $v['id'] }}">{{ $v['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="variant_ids" />
            </div>
        </section>

        <flux:separator />

        {{-- META --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Notes & Status</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Internal notes and whether this spare is available for use.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:textarea
                    wire:model="remark"
                    label="Remark"
                    placeholder="Internal notes about this spare."
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive spares are hidden from order forms but kept for history."
                />
            </div>
        </section>

        <flux:separator />

        {{-- ACTIONS --}}
        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('spare-master.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                {{ $editingId ? 'Save Changes' : 'Create Spare' }}
            </flux:button>
        </div>
    </form>
</div>
