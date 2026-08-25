@php($ETM = \App\Modules\EstimateTemplateMaster\Models\EstimateTemplateMaster::class)
<div>
    <form wire:submit="save" novalidate class="max-w-4xl">
        <div class="mb-6">
            <flux:link :href="route('estimate-template-master.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Estimate Templates
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? 'Edit Template' : 'New Estimate Template' }}</flux:heading>
        </div>

        <flux:separator class="mb-6" />

        <div class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <flux:input wire:model="name" label="Template Name" placeholder="e.g. PMS BASIC" required />
                </div>
                <flux:input wire:model="code" label="Template Code" placeholder="PMS-B" class:input="font-mono uppercase" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:date-picker locale="en-IN" wire:model="effective_date" label="Effective Date" placeholder="From when" with-today selectable-header fixed-weeks type="input" clearable />
                <flux:select wire:model="category" variant="listbox" clearable label="Template Category" placeholder="PMS / Brake / …">
                    @foreach ($ETM::categories() as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="inventory_group_id" variant="listbox" searchable clearable label="Inventory Group" placeholder="Group-wise…" :filter="false">
                    <x-slot name="search">
                        <flux:select.search wire:model.live.debounce.250ms="inventoryGroupSearch" placeholder="Type a group name…" />
                    </x-slot>
                    @foreach ($this->inventoryGroups as $g)
                        <flux:select.option :value="$g->id" wire:key="ig-{{ $g->id }}">{{ $g->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:select wire:model.live="vehicle_brand_id" variant="listbox" searchable clearable label="Vehicle Brand" placeholder="Any brand">
                    @foreach ($this->vehicleBrands as $b)
                        <flux:select.option :value="$b->id" wire:key="vb-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="vehicle_model_id" variant="listbox" searchable clearable label="Model" placeholder="Any model">
                    @foreach ($this->vehicleModels as $m)
                        <flux:select.option :value="$m->id" wire:key="vm-{{ $m->id }}">{{ $m->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="vehicle_variant_id" variant="listbox" searchable clearable label="Variant" placeholder="Any variant">
                    @foreach ($this->vehicleVariants as $v)
                        <flux:select.option :value="$v->id" wire:key="vv-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:select wire:model="service_package_id" variant="listbox" searchable clearable label="Service / Combo / AMC Package" placeholder="Bundle this template to a package…">
                    @foreach ($this->servicePackages as $pkg)
                        <flux:select.option :value="$pkg->id" wire:key="pkg-{{ $pkg->id }}">{{ $pkg->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:switch wire:model="is_active" label="Active" description="Inactive templates are hidden from the estimate's template picker." />
            </div>

            <flux:separator variant="subtle" />

            {{-- LINES --}}
            <div class="flex items-center justify-between">
                <flux:heading size="sm">Template Lines</flux:heading>
                <div class="flex gap-2">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem('spare')">Spare</flux:button>
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem('labour')">Labour</flux:button>
                </div>
            </div>

            @if (count($items) === 0)
                <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                    Add spare and labour lines that this template should pre-fill.
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($items as $i => $item)
                        @php($qty = (float) ($item['default_qty'] ?? 0))
                        @php($rate = (float) ($item['unit_rate'] ?? 0))
                        @php($taxPct = (float) optional($this->taxes->firstWhere('id', $item['tax_id'] ?? null))->gst_percent)
                        @php($lineTotal = round($qty * $rate, 2))
                        @php($taxAmt = round($lineTotal * $taxPct / 100, 2))
                        <div wire:key="tmpl-item-{{ $i }}" class="space-y-2 p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                            <div class="grid grid-cols-1 md:grid-cols-[100px_1fr_auto] gap-2 items-end">
                                <flux:badge size="sm" :color="$item['line_type'] === 'labour' ? 'purple' : 'sky'">{{ ucfirst($item['line_type']) }}</flux:badge>
                                @if ($item['line_type'] === 'spare')
                                    <flux:select wire:model="items.{{ $i }}.spare_id" variant="listbox" searchable size="sm" label="Spare" placeholder="Pick a spare…">
                                        @foreach ($this->spares as $s)
                                            <flux:select.option :value="$s->id" wire:key="ts-{{ $i }}-{{ $s->id }}">{{ $s->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                @else
                                    <flux:select wire:model="items.{{ $i }}.labour_id" variant="listbox" searchable size="sm" label="Labour" placeholder="Pick a labour…">
                                        @foreach ($this->labours as $l)
                                            <flux:select.option :value="$l->id" wire:key="tl-{{ $i }}-{{ $l->id }}">{{ $l->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                @endif
                                <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" class="h-9!" />
                            </div>

                            <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Line description (optional)" />

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <flux:select wire:model="items.{{ $i }}.inventory_group_id" variant="listbox" size="sm" searchable clearable label="Group" placeholder="Group…">
                                    @foreach ($this->inventoryGroupOptions as $g)
                                        <flux:select.option :value="$g->id" wire:key="iig-{{ $i }}-{{ $g->id }}">{{ $g->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="items.{{ $i }}.uom_id" variant="listbox" size="sm" clearable label="UOM" placeholder="Unit…">
                                    @foreach ($this->uoms as $u)
                                        <flux:select.option :value="$u->id" wire:key="iu-{{ $i }}-{{ $u->id }}">{{ $u->code ?? $u->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="items.{{ $i }}.hsn_id" variant="listbox" size="sm" searchable clearable label="HSN" placeholder="HSN…">
                                    @foreach ($this->hsnCodes as $h)
                                        <flux:select.option :value="$h->id" wire:key="ih-{{ $i }}-{{ $h->id }}">{{ $h->code }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model.live="items.{{ $i }}.tax_id" variant="listbox" size="sm" clearable label="Tax" placeholder="GST…">
                                    @foreach ($this->taxes as $tx)
                                        <flux:select.option :value="$tx->id" wire:key="it-{{ $i }}-{{ $tx->id }}">{{ rtrim(rtrim(number_format((float) $tx->gst_percent, 2), '0'), '.') }}%</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 items-end">
                                <flux:input type="number" step="0.01" min="0.01" wire:model.live="items.{{ $i }}.default_qty" size="sm" label="Qty" class:input="text-right font-mono" />
                                <flux:input.group label="Rate">
                                    <flux:input.group.prefix>₹</flux:input.group.prefix>
                                    <flux:input type="number" step="0.01" min="0" wire:model.live="items.{{ $i }}.unit_rate" size="sm" class:input="text-right font-mono" />
                                </flux:input.group>
                                <div class="text-xs text-zinc-500">
                                    <div>Total: <span class="font-mono text-zinc-700 dark:text-zinc-300">₹ {{ number_format($lineTotal, 2) }}</span></div>
                                    <div>Tax: <span class="font-mono">₹ {{ number_format($taxAmt, 2) }}</span></div>
                                </div>
                                <div class="text-sm">
                                    <div class="text-xs text-zinc-500">Net</div>
                                    <div class="font-mono font-medium">₹ {{ number_format($lineTotal + $taxAmt, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <flux:separator variant="subtle" />

            {{-- BROCHURE --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                <div>
                    <flux:input type="file" wire:model="brochureFile" label="Template Brochure" accept=".jpg,.jpeg,.png,.webp,.pdf" />
                    @if ($brochure_path)
                        <div class="mt-1 flex items-center gap-2 text-sm text-zinc-500">
                            <flux:icon.paper-clip class="size-4" />
                            <span>{{ $brochure_name ?? basename($brochure_path) }}</span>
                            <flux:button type="button" size="xs" variant="ghost" icon="x-mark" wire:click="clearBrochure" />
                        </div>
                    @endif
                    <flux:error name="brochureFile" />
                </div>
            </div>

            <flux:textarea wire:model="notes" label="Remarks" rows="2" placeholder="Optional notes / remarks about this template." />
        </div>

        <flux:separator class="mt-6" />
        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('estimate-template-master.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save Changes' : 'Create Template' }}</flux:button>
        </div>
    </form>
</div>
