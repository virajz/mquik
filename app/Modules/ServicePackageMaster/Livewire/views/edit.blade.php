@php($PKG = \App\Modules\ServicePackageMaster\Models\ServicePackageMaster::class)
@php($ATT = \App\Modules\ServicePackageMaster\Models\ServicePackageAttachment::class)
<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-8">
            <flux:link :href="route('service-package-master.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Service Packages
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($name ?: 'Edit Package') : 'New Service Package' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Combo / PMS / AMC / New-Vehicle-Care bundle — services, spares, pricing and usage rules.</flux:text>
        </div>

        <flux:separator />

        {{-- DETAILS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Details</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Name, category, validity and usage rule.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-[1fr_180px] gap-3">
                    <flux:input wire:model="name" label="Package Name" placeholder="STANDARD AMC PACK" required autofocus />
                    <flux:input wire:model="code" label="Code" placeholder="SP-001" class:input="font-mono uppercase tracking-wide" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="service_package_type_id" label="Package Type" variant="listbox" placeholder="Combo / PMS / AMC / New Vehicle Care" clearable searchable>
                        @foreach ($this->packageTypes as $pt)
                            <flux:select.option :value="$pt->id" wire:key="pkgtype-{{ $pt->id }}">{{ $pt->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="usage_rule" label="Usage Rule" variant="listbox" placeholder="One-Time Use / One Vehicle Only…" clearable>
                        @foreach ($PKG::usageRules() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="description" label="Description" placeholder="What this package covers — services, parts, validity, perks." rows="2" />

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input wire:model="validity_months" type="number" min="1" max="120" label="Validity (months)" placeholder="12" />
                    <flux:input wire:model="validity_km" type="number" min="0" label="Validity (km)" placeholder="10000" />
                    <flux:input wire:model="remarks" label="Remarks" placeholder="—" />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- PRICING --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Package Pricing</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Roll-up price, MRP, offer and the saving (profit).</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input.group label="Total (Taxable Value)">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model="total_price" type="number" step="0.01" min="0" placeholder="0.00" class:input="text-right font-mono" />
                    </flux:input.group>
                    <flux:input.group label="Net Price / MRP">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model="net_price" type="number" step="0.01" min="0" placeholder="0.00" class:input="text-right font-mono" />
                    </flux:input.group>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input.group label="Offer Price">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model="offer_price" type="number" step="0.01" min="0" placeholder="0.00" class:input="text-right font-mono" />
                    </flux:input.group>
                    <flux:input.group label="Discount %">
                        <flux:input wire:model="discount_percent" type="number" step="0.01" min="0" max="100" placeholder="0" class:input="text-right font-mono" />
                        <flux:input.group.suffix>%</flux:input.group.suffix>
                    </flux:input.group>
                    <flux:input.group label="Saving (Profit)">
                        <flux:input.group.prefix>₹</flux:input.group.prefix>
                        <flux:input wire:model="saving_price" type="number" step="0.01" min="0" placeholder="0.00" class:input="text-right font-mono" />
                    </flux:input.group>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- INCLUDED SERVICES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Included Services</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Each row is one scheduled service, with its rate and offer.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addService">Add service</flux:button>
                </div>

                @if (count($services) === 0)
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                        No services added yet. Click <span class="font-medium">Add service</span> to start.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($services as $i => $row)
                            <div wire:key="svc-row-{{ $i }}" class="p-3 rounded-md border border-zinc-200 dark:border-zinc-800 space-y-2">
                                <div class="grid grid-cols-1 md:grid-cols-[40px_1fr_100px_120px_120px_40px] gap-2 items-end">
                                    <flux:input wire:model="services.{{ $i }}.sequence_no" type="number" min="1" max="99" size="sm" label="#" class:input="text-center font-mono" />
                                    <flux:select wire:model="services.{{ $i }}.service_type_id" variant="listbox" searchable size="sm" label="Service Type" placeholder="Pick a service type…">
                                        @foreach ($this->serviceTypes as $st)
                                            <flux:select.option :value="$st->id" wire:key="svc-{{ $i }}-{{ $st->id }}">{{ $st->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:input wire:model="services.{{ $i }}.due_after_months" type="number" min="0" max="120" size="sm" label="After (mo)" placeholder="—" />
                                    <flux:input wire:model="services.{{ $i }}.due_after_km" type="number" min="0" size="sm" label="After (km)" placeholder="—" />
                                    <flux:input wire:model="services.{{ $i }}.notes" size="sm" label="Notes" placeholder="—" />
                                    <flux:button type="button" size="sm" variant="ghost" icon="x-mark" wire:click="removeService({{ $i }})" class="h-9!" />
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-6 gap-2">
                                    <flux:input wire:model="services.{{ $i }}.sac" size="sm" label="SAC" placeholder="—" class:input="font-mono" />
                                    <flux:input wire:model="services.{{ $i }}.rate" type="number" step="0.01" min="0" size="sm" label="Rate" placeholder="0.00" class:input="text-right font-mono" />
                                    <flux:input wire:model="services.{{ $i }}.quantity" type="number" step="0.01" min="0" size="sm" label="Qty" placeholder="1" class:input="text-right font-mono" />
                                    <flux:input wire:model="services.{{ $i }}.tax_percent" type="number" step="0.01" min="0" max="100" size="sm" label="Tax %" placeholder="0" class:input="text-right font-mono" />
                                    <flux:input wire:model="services.{{ $i }}.offer_price" type="number" step="0.01" min="0" size="sm" label="Offer" placeholder="0.00" class:input="text-right font-mono" />
                                    <flux:input wire:model="services.{{ $i }}.saving_price" type="number" step="0.01" min="0" size="sm" label="Saving" placeholder="0.00" class:input="text-right font-mono" />
                                </div>
                                <flux:error name="services.{{ $i }}.service_type_id" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <flux:separator />

        {{-- INCLUDED SPARES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Included Spares</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Parts bundled in the package, with pricing.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addSpare">Add spare</flux:button>
                </div>

                @if (count($spares) === 0)
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                        No spares added yet. Click <span class="font-medium">Add spare</span> to start.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($spares as $i => $row)
                            <div wire:key="spare-row-{{ $i }}" class="p-3 rounded-md border border-zinc-200 dark:border-zinc-800 space-y-2">
                                <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2 items-end">
                                    <flux:select wire:model.live="spares.{{ $i }}.spare_id" variant="listbox" searchable clearable :filter="false" size="sm" label="Spare" placeholder="Pick a spare…">
                                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="spares.{{ $i }}.spareSearch" placeholder="Search spare…" /></x-slot>
                                        @foreach ($this->spareOptions($i) as $sp)<flux:select.option :value="$sp->id" wire:key="sp-{{ $i }}-{{ $sp->id }}">{{ $sp->name }}</flux:select.option>@endforeach
                                    </flux:select>
                                    <flux:input wire:model="spares.{{ $i }}.description" size="sm" label="Description" placeholder="Part description" />
                                    <flux:button type="button" size="sm" variant="ghost" icon="x-mark" wire:click="removeSpare({{ $i }})" class="h-9!" />
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-6 gap-2">
                                    <flux:input wire:model="spares.{{ $i }}.sac" size="sm" label="HSN/SAC" placeholder="—" class:input="font-mono" />
                                    <flux:input wire:model="spares.{{ $i }}.rate" type="number" step="0.01" min="0" size="sm" label="Rate" placeholder="0.00" class:input="text-right font-mono" />
                                    <flux:input wire:model="spares.{{ $i }}.quantity" type="number" step="0.01" min="0" size="sm" label="Qty" placeholder="1" class:input="text-right font-mono" />
                                    <flux:input wire:model="spares.{{ $i }}.tax_percent" type="number" step="0.01" min="0" max="100" size="sm" label="Tax %" placeholder="0" class:input="text-right font-mono" />
                                    <flux:input wire:model="spares.{{ $i }}.offer_price" type="number" step="0.01" min="0" size="sm" label="Offer" placeholder="0.00" class:input="text-right font-mono" />
                                    <flux:input wire:model="spares.{{ $i }}.saving_price" type="number" step="0.01" min="0" size="sm" label="Saving" placeholder="0.00" class:input="text-right font-mono" />
                                </div>
                                <flux:error name="spares.{{ $i }}.description" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <flux:separator />

        {{-- TERMS & ATTACHMENTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Terms & Files</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Usage rules text, brochure and notes.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:textarea wire:model="terms_conditions" label="T&Cs / Usage Rule Notes" placeholder="One-time use, one vehicle only, non-transferable…" rows="2" />

                <div class="pt-2">
                    <div class="flex items-center justify-between mb-2">
                        <flux:text size="sm" class="font-medium">Attachments</flux:text>
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addAttachment">Add file</flux:button>
                    </div>
                    @forelse ($attachments as $i => $att)
                        <div wire:key="pkg-att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2 items-end mb-2 p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
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
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">Package brochure / package notes.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Status</flux:heading>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:switch wire:model="is_amc" label="Is AMC Package" description="AMCs trigger AMC-specific reminders; combo packs don't." />
                    <flux:switch wire:model="is_active" label="Active" description="Inactive packages are hidden from job-card pickers." />
                </div>
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('service-package-master.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
        </div>
    </form>
</div>
