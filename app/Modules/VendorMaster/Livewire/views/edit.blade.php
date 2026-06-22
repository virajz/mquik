<div>
    <form wire:submit="save" class="max-w-4xl">
        {{-- Page header --}}
        <div class="mb-8">
            <flux:link :href="route('vendor-master.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" />
                Vendors
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">
                {{ $editingId ? $name : 'New Vendor' }}
            </flux:heading>
        </div>

        <flux:separator />

        {{-- IDENTITY --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Identity</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Vendor code, name, and the categories they supply.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input
                        wire:model="vendor_code"
                        label="Vendor Code"
                        placeholder="VND-00001"
                        required
                        class:input="font-mono uppercase tracking-wide"
                    />
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Name"
                            placeholder="Vendor name"
                            required
                            autofocus
                        />
                    </div>
                </div>

                <flux:field>
                    <flux:label>Vendor Types <span class="text-red-500">*</span></flux:label>
                    <div class="flex items-stretch gap-2">
                        <div class="flex-1 min-w-0">
                            <flux:select
                                wire:model="vendor_type_ids"
                                variant="listbox"
                                multiple
                                searchable
                                placeholder="Pick one or more types…"
                            >
                                @foreach ($vendorTypes as $t)
                                    <flux:select.option :value="$t->id" wire:key="vt-{{ $t->id }}">{{ $t->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                        @can('vendor_type_master.create')
                            <flux:tooltip content="Quick add a new vendor type">
                                <flux:button
                                    icon="plus"
                                    variant="ghost"
                                    type="button"
                                    x-on:click="$flux.modal('vendor-type-quick-add').show()"
                                />
                            </flux:tooltip>
                        @endcan
                    </div>
                    <flux:error name="vendor_type_ids" />
                    <flux:error name="vendor_type_ids.0" />
                </flux:field>

                <flux:field>
                    <flux:label>Parts Brands Supplied</flux:label>
                    <flux:description>If this vendor supplies parts, pick the brands they carry.</flux:description>
                    <div class="flex items-stretch gap-2">
                        <div class="flex-1 min-w-0">
                            <flux:select
                                wire:model="spare_brand_ids"
                                variant="listbox"
                                multiple
                                searchable
                                placeholder="Pick one or more brands…"
                            >
                                @foreach ($this->spareBrands as $b)
                                    <flux:select.option :value="$b->id" wire:key="sb-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                        @can('spare_brand_master.create')
                            <flux:tooltip content="Quick add a new parts brand">
                                <flux:button
                                    icon="plus"
                                    variant="ghost"
                                    type="button"
                                    x-on:click="$flux.modal('spare-brand-quick-add').show()"
                                />
                            </flux:tooltip>
                        @endcan
                    </div>
                    <flux:error name="spare_brand_ids" />
                    <flux:error name="spare_brand_ids.0" />
                </flux:field>
            </div>
        </section>

        <flux:separator />

        {{-- CONTACT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Contact</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">How to reach them.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Phone <span class="text-red-500">*</span></flux:label>
                        <flux:input.group>
                            <flux:input.group.prefix>+91</flux:input.group.prefix>
                            <flux:input
                                wire:model="phone"
                                mask="99999 99999"
                                placeholder="98765 43210"
                                inputmode="numeric"
                                required
                            />
                        </flux:input.group>
                        <flux:error name="phone" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Alternate Phone</flux:label>
                        <flux:input.group>
                            <flux:input.group.prefix>+91</flux:input.group.prefix>
                            <flux:input
                                wire:model="alternate_phone"
                                mask="99999 99999"
                                placeholder="98765 43210"
                                inputmode="numeric"
                            />
                        </flux:input.group>
                        <flux:error name="alternate_phone" />
                    </flux:field>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        wire:model="email"
                        type="email"
                        label="Email"
                        placeholder="vendor@example.com"
                        icon="envelope"
                    />
                    <flux:input
                        wire:model="secondary_email"
                        type="email"
                        label="Secondary Email"
                        placeholder="optional second contact"
                        icon="envelope"
                    />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ADDRESS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Address</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Where they're based — used on POs and bills.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:textarea
                    wire:model="address"
                    label="Street / Building"
                    placeholder="House no, street, area…"
                    rows="2"
                />

                <flux:select
                    wire:model="region_id"
                    variant="combobox"
                    label="Region"
                    clearable
                >
                    <x-slot name="input">
                        <flux:select.input wire:model="regionSearch" placeholder="Search city, area, or pincode…" />
                    </x-slot>

                    @foreach ($this->regions as $r)
                        <flux:select.option :value="$r['id']" wire:key="r-{{ $r['id'] }}">
                            {{ $r['name'] }}{{ $r['chain'] ? ' — '.$r['chain'] : '' }} ({{ ucfirst($r['kind']) }})
                        </flux:select.option>
                    @endforeach

                    @can('region_master.create')
                        @foreach (['pincode' => 'Pincode', 'area' => 'Area', 'city' => 'City', 'state' => 'State'] as $k => $label)
                            <flux:select.option.create
                                wire:click="createRegion('{{ $k }}')"
                                min-length="2"
                            >
                                Create as {{ $label }} "<span wire:text="regionSearch"></span>"
                            </flux:select.option.create>
                        @endforeach
                    @endcan
                </flux:select>
            </div>
        </section>

        <flux:separator />

        {{-- KYC --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">KYC</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Tax identifiers and scanned ID proofs.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        wire:model="gstin"
                        label="GSTIN"
                        placeholder="22ABCDE1234F1Z5"
                        maxlength="15"
                        class:input="font-mono uppercase tracking-wide"
                    />
                    <flux:select wire:model="gst_type_id" variant="listbox" searchable clearable label="GST Type" placeholder="Regular / Composition…">
                        @foreach ($this->gstTypes as $gt)
                            <flux:select.option :value="$gt->id" wire:key="gt-{{ $gt->id }}">{{ $gt->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:select wire:model="service_specialist_ids" variant="listbox" multiple searchable clearable label="Service Specialities" placeholder="Denting, Painting, AC…">
                    @foreach ($this->serviceSpecialistOptions as $sp)
                        <flux:select.option :value="$sp->id" wire:key="ssp-{{ $sp->id }}">{{ $sp->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="service_specialist_ids" />

                {{-- Aadhar number + scan --}}
                <div class="space-y-2">
                    <flux:input
                        wire:model="aadhar"
                        label="Aadhar Number"
                        mask="9999 9999 9999"
                        placeholder="0000 0000 0000"
                        class:input="font-mono uppercase tracking-wide"
                        inputmode="numeric"
                    />
                    @if ($aadhar_file)
                        <flux:file-item
                            :heading="$aadhar_file->getClientOriginalName()"
                            :size="$aadhar_file->getSize()"
                        >
                            <x-slot name="actions">
                                <flux:file-item.remove wire:click="removeAadharFile" />
                            </x-slot>
                        </flux:file-item>
                    @elseif ($aadhar_file_path && $editingId)
                        <flux:file-item :heading="$aadhar_file_name ?? 'Aadhar document'">
                            <x-slot name="actions">
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    icon="arrow-down-tray"
                                    :href="route('vendor-master.file', ['vendor' => $editingId, 'type' => 'aadhar'])"
                                    target="_blank"
                                />
                                <flux:file-item.remove wire:click="removeAadharFile" />
                            </x-slot>
                        </flux:file-item>
                    @else
                        <flux:file-upload wire:model="aadhar_file" accept="image/jpeg,image/png,application/pdf">
                            <flux:file-upload.dropzone
                                heading="Upload Aadhar scan"
                                text="JPG, PNG, or PDF up to 5 MB"
                            />
                        </flux:file-upload>
                    @endif
                    <flux:error name="aadhar_file" />
                </div>

                {{-- PAN number + scan --}}
                <div class="space-y-2">
                    <flux:input
                        wire:model="pan"
                        label="PAN Number"
                        placeholder="ABCDE1234F"
                        maxlength="10"
                        class:input="font-mono uppercase tracking-wide"
                    />
                    @if ($pan_file)
                        <flux:file-item
                            :heading="$pan_file->getClientOriginalName()"
                            :size="$pan_file->getSize()"
                        >
                            <x-slot name="actions">
                                <flux:file-item.remove wire:click="removePanFile" />
                            </x-slot>
                        </flux:file-item>
                    @elseif ($pan_file_path && $editingId)
                        <flux:file-item :heading="$pan_file_name ?? 'PAN document'">
                            <x-slot name="actions">
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    icon="arrow-down-tray"
                                    :href="route('vendor-master.file', ['vendor' => $editingId, 'type' => 'pan'])"
                                    target="_blank"
                                />
                                <flux:file-item.remove wire:click="removePanFile" />
                            </x-slot>
                        </flux:file-item>
                    @else
                        <flux:file-upload wire:model="pan_file" accept="image/jpeg,image/png,application/pdf">
                            <flux:file-upload.dropzone
                                heading="Upload PAN scan"
                                text="JPG, PNG, or PDF up to 5 MB"
                            />
                        </flux:file-upload>
                    @endif
                    <flux:error name="pan_file" />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- BANKING --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Banking</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Used to pay vendor bills via NEFT / RTGS.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select
                        wire:model="bank_id"
                        variant="combobox"
                        label="Bank Name"
                        clearable
                    >
                        <x-slot name="input">
                            <flux:select.input wire:model="bankSearch" placeholder="Pick or type to add…" />
                        </x-slot>
                        @foreach ($this->banks as $b)
                            <flux:select.option :value="$b->id" wire:key="bank-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                        @endforeach
                        @can('bank_master.create')
                            <flux:select.option.create wire:click="createBank" min-length="2">
                                Create "<span wire:text="bankSearch"></span>"
                            </flux:select.option.create>
                        @endcan
                    </flux:select>
                    <flux:input wire:model="bank_branch" label="Branch" placeholder="e.g. SATELLITE" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input
                        wire:model="ifsc"
                        label="IFSC"
                        placeholder="HDFC0000001"
                        maxlength="11"
                        class:input="font-mono uppercase tracking-wide"
                    />
                    <flux:input
                        wire:model="account_no"
                        label="Account No"
                        placeholder="Account number"
                        class:input="font-mono tracking-wide"
                    />
                    <flux:input
                        wire:model="account_holder"
                        label="Account Holder"
                        placeholder="Name on account"
                    />
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- CREDIT --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Credit</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Used on POs, aging reports, and credit checks.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Credit Days</flux:label>
                        <flux:input.group>
                            <flux:input wire:model="credit_days" type="number" min="0" inputmode="numeric" />
                            <flux:input.group.suffix>days</flux:input.group.suffix>
                        </flux:input.group>
                        <flux:error name="credit_days" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Credit Limit</flux:label>
                        <flux:input.group>
                            <flux:input.group.prefix>₹</flux:input.group.prefix>
                            <flux:input wire:model="credit_limit" type="number" min="0" step="0.01" inputmode="decimal" />
                        </flux:input.group>
                        <flux:error name="credit_limit" />
                    </flux:field>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- TERMS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Terms</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">
                    Free-form key/value terms — Payment Term, Delivery Term, Warranty, Returns Policy, etc.
                </flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                @forelse ($terms as $i => $row)
                    <div wire:key="term-{{ $i }}-{{ $row['id'] ?? 'new' }}"
                        class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 space-y-3">
                        <div class="flex items-end justify-end">
                            <flux:button size="xs" variant="ghost" icon="trash"
                                wire:click="removeTerm({{ $i }})" type="button" />
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <flux:input
                                wire:model="terms.{{ $i }}.name"
                                label="Name"
                                placeholder="e.g. Payment Term"
                                maxlength="100"
                            />
                            <div class="md:col-span-2">
                                <flux:input
                                    wire:model="terms.{{ $i }}.value"
                                    label="Value"
                                    placeholder="e.g. Net 30 days"
                                    maxlength="1000"
                                />
                            </div>
                        </div>
                        <flux:error name="terms.{{ $i }}.name" />
                        <flux:error name="terms.{{ $i }}.value" />
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700 p-6 text-center">
                        <flux:text size="sm" class="text-zinc-500">No terms yet.</flux:text>
                    </div>
                @endforelse

                <flux:button size="sm" variant="ghost" icon="plus" wire:click="addTerm" type="button">
                    Add term
                </flux:button>
            </div>
        </section>

        <flux:separator />

        {{-- NOTES & STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Notes & Status</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Internal context and visibility.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:textarea
                    wire:model="notes"
                    label="Internal Notes"
                    placeholder="Anything the team should know about this vendor"
                    rows="3"
                />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive vendors won't appear in purchase order or bill entry dropdowns."
                />
            </div>
        </section>

        {{-- Sticky bottom action bar --}}
        <div class="sticky bottom-0 -mx-6 lg:-mx-8 px-6 lg:px-8 py-3 mt-4 border-t border-zinc-200 dark:border-zinc-700 bg-white/85 dark:bg-zinc-900/85 backdrop-blur">
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" :href="route('vendor-master.index')" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary" icon="check">
                    {{ $editingId ? 'Save changes' : 'Create' }}
                </flux:button>
            </div>
        </div>
    </form>

    {{-- Quick-add modals (outside the main form so submitting them doesn't post the parent). --}}
    @can('vendor_type_master.create')
        <flux:modal name="vendor-type-quick-add" class="md:w-md">
            <form wire:submit.prevent="createVendorType" class="space-y-5">
                <div>
                    <flux:heading size="lg">Quick add Vendor Type</flux:heading>
                    <flux:subheading>Just the name. Add more details later from the Vendor Types page.</flux:subheading>
                </div>
                <flux:input
                    wire:model="vendorTypeSearch"
                    label="Name"
                    placeholder="e.g. CORPORATE"
                    autofocus
                    required
                />
                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="check">Add</flux:button>
                </div>
            </form>
        </flux:modal>
    @endcan

    @can('spare_brand_master.create')
        <flux:modal name="spare-brand-quick-add" class="md:w-md">
            <form wire:submit.prevent="createSpareBrand" class="space-y-5">
                <div>
                    <flux:heading size="lg">Quick add Parts Brand</flux:heading>
                    <flux:subheading>Just the name. Add more details later from the Parts Brands page.</flux:subheading>
                </div>
                <flux:input
                    wire:model="spareBrandSearch"
                    label="Name"
                    placeholder="e.g. BOSCH"
                    autofocus
                    required
                />
                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="check">Add</flux:button>
                </div>
            </form>
        </flux:modal>
    @endcan
</div>
