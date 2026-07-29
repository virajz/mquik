@php($AMC = \App\Modules\VehicleAmc\Models\VehicleAmc::class)
@php($ITEM = \App\Modules\VehicleAmc\Models\VehicleAmcItem::class)
@php($ATT = \App\Modules\VehicleAmc\Models\VehicleAmcAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('vehicle-amc.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Vehicle AMC
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($amc_no ?: 'Edit AMC') : 'New Vehicle AMC' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Sell / manage an Annual Maintenance Contract.</flux:text>
        </div>

        <flux:separator />

        {{-- CONTRACT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Contract</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Customer, package and validity.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="customer_id" variant="listbox" searchable clearable :filter="false" label="Customer" placeholder="Customer…" autofocus>
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="customerSearch" placeholder="Search customer…" /></x-slot>
                        @foreach ($this->customers as $c)<flux:select.option :value="$c->id" wire:key="cu-{{ $c->id }}">{{ trim($c->first_name.' '.$c->last_name) }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="customer_vehicle_id" variant="listbox" searchable clearable :filter="false" label="Vehicle" placeholder="Registration…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="vehicleSearch" placeholder="Search vehicle…" /></x-slot>
                        @foreach ($this->vehicles as $vh)<flux:select.option :value="$vh->id" wire:key="vh-{{ $vh->id }}">{{ $vh->registration_no }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="sold_by_id" variant="listbox" searchable clearable label="AMC Sold By" placeholder="Advisor…">
                        @foreach ($this->employees as $e)<flux:select.option :value="$e->id" wire:key="sb-{{ $e->id }}">{{ $e->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <flux:select wire:model="amc_package" variant="listbox" clearable label="AMC Package" placeholder="Silver / Gold…">
                        @foreach ($AMC::packages() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="amc_validity" variant="listbox" clearable label="Validity" placeholder="12 / 24 / 36 M">
                        @foreach ($AMC::validities() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="services_limit" variant="listbox" clearable label="Usage Limit" placeholder="No. of services">
                        @foreach ($AMC::serviceLimits() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input wire:model="services_availed" type="number" min="0" label="Services Availed" class:input="text-right font-mono" required />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <flux:input wire:model="start_date" type="date" label="Start Date" />
                    <div>
                        <flux:input wire:model="end_date" type="date" label="End Date" />
                        <flux:error name="end_date" />
                    </div>
                    <flux:input.group label="AMC Amount">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model="amount" type="number" step="0.01" min="0" class:input="text-right font-mono" />
                    </flux:input.group>
                    <flux:select wire:model="workshop_department_id" variant="listbox" clearable label="Department" placeholder="Dept…">
                        @foreach ($this->departments as $d)<flux:select.option :value="$d->id" wire:key="dp-{{ $d->id }}">{{ $d->name }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- INCLUDED ITEMS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Included Services / Spares</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">What the package covers, with any discount.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add item</flux:button>
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
                        <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Included service / spare (required)" required />
                        <flux:error name="items.{{ $i }}.description" />
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <flux:select wire:model="items.{{ $i }}.uom_id" variant="listbox" size="sm" clearable label="UOM" placeholder="Unit…">
                                @foreach ($this->uoms as $u)<flux:select.option :value="$u->id" wire:key="uom-{{ $i }}-{{ $u->id }}">{{ $u->code ?? $u->name }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:select wire:model="items.{{ $i }}.tax_id" variant="listbox" size="sm" clearable label="Tax" placeholder="GST…">
                                @foreach ($this->taxes as $tx)<flux:select.option :value="$tx->id" wire:key="tx-{{ $i }}-{{ $tx->id }}">{{ rtrim(rtrim(number_format((float) $tx->gst_percent, 2), '0'), '.') }}%</flux:select.option>@endforeach
                            </flux:select>
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0.01" size="sm" label="Qty" class:input="text-right font-mono" required />
                            <flux:input wire:model="items.{{ $i }}.discount_value" type="number" step="0.01" min="0" size="sm" label="Discount" class:input="text-right font-mono" />
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <flux:separator />

        {{-- STATUS & TERMS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Status & Terms</flux:heading>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="status" variant="listbox" label="AMC Status" required>
                        @foreach ($AMC::statuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="payment_status" variant="listbox" label="Payment Status" required>
                        @foreach ($AMC::paymentStatuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <flux:textarea wire:model="terms_conditions" label="Terms & Conditions" rows="3" placeholder="AMC T&Cs — effective after full payment, non-transferable, etc." />

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
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Signed AMC agreement / advisor / customer note.</div>
                    @endforelse
                </div>

                <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="AMC remarks." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('vehicle-amc.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create AMC' }}</flux:button>
        </div>
    </form>
</div>
