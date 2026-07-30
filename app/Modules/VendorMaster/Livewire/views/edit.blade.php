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
                            label="Trade Name"
                            placeholder="Vendor trade name"
                            required
                            autofocus
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input wire:model="legal_name" label="Legal Name" placeholder="Registered legal name" />
                    <flux:input wire:model="registration_date" type="date" label="Date of Registration" />
                    <flux:input wire:model="reference" label="Reference" placeholder="Referred by / source" />
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
                            {{-- ~770 brands, so options are queried per keystroke. --}}
                            <flux:select
                                wire:model="spare_brand_ids"
                                variant="listbox"
                                multiple
                                searchable
                                clear="close"
                                :filter="false"
                                placeholder="Pick one or more brands…"
                            >
                                <x-slot name="search">
                                    <flux:select.search wire:model.live.debounce.250ms="spareBrandSearch" placeholder="Type a brand name…" />
                                </x-slot>
                                @forelse ($this->spareBrands as $b)
                                    <flux:select.option :value="$b->id" wire:key="sb-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                                @empty
                                    <flux:select.option value="" disabled>No matching brands.</flux:select.option>
                                @endforelse
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

                <flux:field>
                    <flux:label>Inventory Groups Supplied</flux:label>
                    <flux:description>Which part categories this vendor can source &mdash; drives sourcing lookups.</flux:description>
                    {{-- ~1,000 sub-groups, so options are queried per keystroke. --}}
                    <flux:select
                        wire:model="inventory_group_ids"
                        variant="listbox"
                        multiple
                        searchable
                        clearable
                        clear="close"
                        :filter="false"
                        placeholder="Pick one or more groups…"
                    >
                        <x-slot name="search">
                            <flux:select.search wire:model.live.debounce.250ms="inventoryGroupSearch" placeholder="Type a group name or code…" />
                        </x-slot>
                        @forelse ($this->inventoryGroupOptions as $g)
                            <flux:select.option :value="$g->id" wire:key="ig-{{ $g->id }}">
                                {{ $g->parent ? $g->parent->name.' › ' : '' }}{{ $g->name }}
                            </flux:select.option>
                        @empty
                            <flux:select.option value="" disabled>No matching groups.</flux:select.option>
                        @endforelse
                    </flux:select>
                    <flux:error name="inventory_group_ids" />
                    <flux:error name="inventory_group_ids.0" />
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
                    <flux:input wire:model="contact_person1" label="Contact Person 1" placeholder="Primary contact" />
                    <flux:input wire:model="contact_person2" label="Contact Person 2" placeholder="Secondary contact" />
                </div>

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
                    label="Head Office Address"
                    placeholder="House no, street, area…"
                    rows="2"
                />

                <flux:textarea
                    wire:model="branch_address"
                    label="Branch Address"
                    placeholder="Branch / warehouse address (if any)"
                    rows="2"
                />

                <flux:input wire:model="pincode" label="Pincode" mask="999999" placeholder="380001" inputmode="numeric" class:input="font-mono" />

                <flux:select
                    wire:model="region_id"
                    variant="combobox"
                    label="Region (State / City / Area)"
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="gst_registration_type" variant="listbox" clearable label="GST Registration Type" placeholder="Regular / SEZ / Export…">
                        @foreach (\App\Modules\VendorMaster\Models\VendorMaster::gstRegistrationTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input wire:model="udyam_no" label="Udyam Registration No." placeholder="UDYAM-XX-00-0000000" class:input="font-mono uppercase" />
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

        {{-- BUSINESS & CLASSIFICATION --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Business & Classification</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">How the vendor is constituted, classified and categorised.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="classification" variant="listbox" clearable label="Vendor Classification" placeholder="Manufacturer / Dealer…">
                        @foreach (\App\Modules\VendorMaster\Models\VendorMaster::classifications() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="constitution" variant="listbox" clearable label="Constitution of Business" placeholder="Proprietorship / LLP…">
                        @foreach (\App\Modules\VendorMaster\Models\VendorMaster::constitutions() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select wire:model="vendor_category" variant="listbox" clearable label="Vendor Category" placeholder="Genuine / OEM…">
                        @foreach (\App\Modules\VendorMaster\Models\VendorMaster::vendorCategories() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="msme_type" variant="listbox" clearable label="MSME Type" placeholder="Micro / Small…">
                        @foreach (\App\Modules\VendorMaster\Models\VendorMaster::msmeTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="msme_activity" variant="listbox" clearable label="MSME Major Activity" placeholder="Trading / Services…">
                        @foreach (\App\Modules\VendorMaster\Models\VendorMaster::msmeActivities() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="payment_terms" variant="listbox" clearable label="Payment Terms" placeholder="Advance / 30 Days…">
                        @foreach (\App\Modules\VendorMaster\Models\VendorMaster::paymentTermsOptions() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="delivery_method" variant="listbox" clearable label="Delivery Method" placeholder="Self Pickup / Courier…">
                        @foreach (\App\Modules\VendorMaster\Models\VendorMaster::deliveryMethods() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
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
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <flux:select wire:model="terms.{{ $i }}.term_type" variant="listbox" clearable label="Policy Type" placeholder="Payment / Delivery…">
                                @foreach (\App\Modules\VendorMaster\Models\VendorTerm::termTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                            </flux:select>
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

        {{-- DOCUMENTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Documents</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">GST / MSME certificate, cancelled cheque, passbook, signed T&Cs.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex items-center justify-between">
                    <flux:text size="sm" class="font-medium">Attachments</flux:text>
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addAttachment">Add file</flux:button>
                </div>
                @forelse ($attachments as $i => $att)
                    <div wire:key="vendor-att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2 items-end p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <flux:select wire:model="attachments.{{ $i }}.attachment_type" variant="listbox" size="sm" clearable label="Type" placeholder="Type…">
                            @foreach (\App\Modules\VendorMaster\Models\VendorAttachment::attachmentTypes() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                        </flux:select>
                        <div>
                            <flux:input type="file" wire:model="attachmentFiles.{{ $i }}" size="sm" accept=".jpg,.jpeg,.png,.webp,.pdf" />
                            @if (! empty($att['path']))<flux:text size="sm" class="text-zinc-500 mt-1">Current: {{ $att['original_name'] ?? basename($att['path']) }}</flux:text>@endif
                            <flux:error name="attachmentFiles.{{ $i }}" />
                        </div>
                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeAttachment({{ $i }})" class="h-9!" />
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-4 text-center text-sm text-zinc-500">No documents yet.</div>
                @endforelse
            </div>
        </section>

        <flux:separator />

        {{-- PERFORMANCE & RATING --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Performance & Rating</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Scorecard — summarised manually until sourced from transactions.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:select wire:model="rating" variant="listbox" clearable label="Vendor Rating" placeholder="1–5 Star" class="md:max-w-xs">
                    @foreach (\App\Modules\VendorMaster\Models\VendorMaster::ratings() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                </flux:select>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <flux:input.group label="On-Time Delivery">
                        <flux:input wire:model="on_time_delivery_percent" type="number" step="0.01" min="0" max="100" placeholder="0" class:input="text-right font-mono" />
                        <flux:input.group.suffix>%</flux:input.group.suffix>
                    </flux:input.group>
                    <flux:input.group label="Parts Return">
                        <flux:input wire:model="parts_return_percent" type="number" step="0.01" min="0" max="100" placeholder="0" class:input="text-right font-mono" />
                        <flux:input.group.suffix>%</flux:input.group.suffix>
                    </flux:input.group>
                    <flux:input.group label="Return Rejection">
                        <flux:input wire:model="return_rejection_percent" type="number" step="0.01" min="0" max="100" placeholder="0" class:input="text-right font-mono" />
                        <flux:input.group.suffix>%</flux:input.group.suffix>
                    </flux:input.group>
                    <flux:input.group label="Response Time">
                        <flux:input wire:model="avg_response_hours" type="number" step="0.01" min="0" placeholder="0" class:input="text-right font-mono" />
                        <flux:input.group.suffix>hrs</flux:input.group.suffix>
                    </flux:input.group>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- NOTES & STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Notes & Status</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Lifecycle, internal context and visibility.</flux:text>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                    <flux:select wire:model.live="vendor_status" variant="listbox" label="Vendor Status" required>
                        @foreach (\App\Modules\VendorMaster\Models\VendorMaster::vendorStatuses() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <div x-show="$wire.vendor_status === 'blacklisted'" x-cloak>
                        <flux:select wire:model="blacklist_reason" variant="listbox" clearable label="Blacklist Reason" placeholder="Why blacklisted…">
                            @foreach (\App\Modules\VendorMaster\Models\VendorMaster::blacklistReasons() as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:error name="blacklist_reason" />
                    </div>
                </div>

                <flux:textarea
                    wire:model="notes"
                    label="Internal Notes / Remarks"
                    placeholder="Anything the team should know about this vendor"
                    rows="3"
                />

                <flux:switch
                    wire:model="is_active"
                    label="Active (visible in pickers)"
                    description="Inactive vendors won't appear in purchase order or bill entry dropdowns. Independent of the lifecycle status above."
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
