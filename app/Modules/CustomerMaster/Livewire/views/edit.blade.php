<div>
    <form wire:submit="save" novalidate class="max-w-4xl">
        {{-- Page header --}}
        <div class="mb-8 flex items-start justify-between gap-4">
            <div>
                <flux:link :href="route('customer-master.index')" variant="ghost" class="text-xs">
                    <flux:icon.chevron-left class="inline size-3 -mt-0.5" />
                    Customers
                </flux:link>
                <flux:heading size="xl" level="1" class="mt-1">
                    {{ $editingId
                        ? trim(implode(' ', array_filter([$first_name, $middle_name, $last_name])))
                        : 'New Customer' }}
                </flux:heading>
            </div>
            @if ($editingId)
                <flux:modal.trigger name="record-panel">
                    <flux:button type="button" size="sm" variant="ghost" icon="squares-2x2">Related</flux:button>
                </flux:modal.trigger>
            @endif
        </div>

        <flux:separator />

        {{-- IDENTITY --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Identity</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">
                    Who they are. Company name is required once a GST type or number is set.
                </flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input
                        wire:model="first_name"
                        label="First Name"
                        placeholder="First name"
                        required
                        autofocus
                    />
                    <flux:input
                        wire:model="middle_name"
                        label="Middle Name"
                        placeholder="Optional"
                    />
                    <flux:input
                        wire:model="last_name"
                        label="Last Name"
                        placeholder="Optional"
                    />
                </div>

                <flux:input
                    wire:model="company_name"
                    label="Company Name"
                    placeholder="Trading name as it appears on the GST certificate"
                    icon="building-office-2"
                    :required="$this->requiresCompanyNameForDisplay"
                    :description="$this->requiresCompanyNameForDisplay
                        ? 'Required — this customer is GST-registered.'
                        : 'Optional for individuals. Becomes required once a GST type or number is set.'"
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="business_type_id" variant="combobox" label="Customer Type" required>
                        <x-slot name="input">
                            <flux:select.input wire:model="businessTypeSearch" placeholder="Pick or type to add…" />
                        </x-slot>
                        @foreach ($businessTypes as $bt)
                            <flux:select.option :value="$bt->id" wire:key="bt-{{ $bt->id }}">{{ $bt->name }}</flux:select.option>
                        @endforeach
                        @can('business_type_master.create')
                            <flux:select.option.create wire:click="createBusinessType" min-length="2">
                                Create "<span wire:text="businessTypeSearch"></span>"
                            </flux:select.option.create>
                        @endcan
                    </flux:select>

                    <flux:select wire:model.live="gst_type_id" variant="listbox" label="GST Type" placeholder="Select GST type…" clearable searchable>
                        @foreach ($gstTypes as $gt)
                            <flux:select.option :value="$gt->id" wire:key="gst-{{ $gt->id }}">{{ $gt->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    {{-- An unregistered customer holds no GSTIN by definition, so
                         the field states that rather than sitting empty. --}}
                    @if ($this->isUnregistered)
                        <flux:field>
                            <flux:label>GST Number</flux:label>
                            <flux:input value="UNREGISTERED" readonly class:input="font-mono tracking-wide text-zinc-500" />
                            <flux:description>This customer is not GST-registered.</flux:description>
                        </flux:field>
                    @else
                        <flux:input
                            wire:model.blur="gstin"
                            label="GST Number"
                            placeholder="22ABCDE1234F1Z5"
                            maxlength="15"
                            class:input="font-mono uppercase tracking-wide"
                        />
                    @endif

                    <flux:field>
                        <flux:label>Referred by</flux:label>
                        <div class="flex items-stretch gap-2">
                            <div class="flex-1 min-w-0">
                                <flux:select
                                    wire:model="referred_by_customer_id"
                                    variant="listbox"
                                    searchable
                                    placeholder="Optional — search by name or phone…"
                                    clearable
                                >
                                    @foreach ($this->referrers as $r)
                                        <flux:select.option :value="$r->id">
                                            {{ $r->name }}{{ $r->phone ? ' — +91 '.$r->phone : '' }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            @can('customer_master.create')
                                <flux:tooltip content="Quick add a new customer">
                                    <flux:button
                                        icon="plus"
                                        variant="ghost"
                                        type="button"
                                        x-on:click="$flux.modal('customer-quick-add').show()"
                                    />
                                </flux:tooltip>
                            @endcan
                        </div>
                    </flux:field>
                </div>
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
                        placeholder="customer@example.com"
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

        {{-- ADDRESSES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Addresses</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">
                    Add one or more. The primary is used for pickup, delivery and reports.
                </flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                @forelse ($addresses as $i => $row)
                    <div wire:key="addr-{{ $i }}-{{ $row['id'] ?? 'new' }}"
                        class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                @if ($row['is_primary'])
                                    <flux:badge color="lime" size="sm" icon="star">Primary</flux:badge>
                                @else
                                    <flux:button size="xs" variant="ghost" icon="star"
                                        wire:click="setPrimary({{ $i }})" type="button">
                                        Make primary
                                    </flux:button>
                                @endif
                            </div>
                            <flux:button size="xs" variant="ghost" icon="trash"
                                wire:click="removeAddress({{ $i }})" type="button" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <flux:input
                                wire:model="addresses.{{ $i }}.label"
                                label="Label"
                                placeholder="Home / Office / Workshop"
                                maxlength="50"
                            />
                            <div class="md:col-span-2">
                                <flux:select
                                    wire:model="addresses.{{ $i }}.region_id"
                                    variant="combobox"
                                    label="Region"
                                    clearable
                                >
                                    <x-slot name="input">
                                        <flux:select.input
                                            wire:model="addresses.{{ $i }}.regionSearch"
                                            placeholder="Search city, area, or pincode…"
                                        />
                                    </x-slot>

                                    @foreach ($this->regions as $r)
                                        <flux:select.option :value="$r['id']" wire:key="r-{{ $i }}-{{ $r['id'] }}">
                                            {{ $r['name'] }}{{ $r['chain'] ? ' — '.$r['chain'] : '' }} ({{ ucfirst($r['kind']) }})
                                        </flux:select.option>
                                    @endforeach

                                    @can('region_master.create')
                                        @foreach (['pincode' => 'Pincode', 'area' => 'Area', 'city' => 'City', 'state' => 'State'] as $k => $label)
                                            <flux:select.option.create
                                                wire:click="createRegionForAddress({{ $i }}, '{{ $k }}')"
                                                min-length="2"
                                            >
                                                Create as {{ $label }} "<span wire:text="addresses.{{ $i }}.regionSearch"></span>"
                                            </flux:select.option.create>
                                        @endforeach
                                    @endcan
                                </flux:select>
                            </div>
                        </div>

                        <flux:textarea
                            wire:model="addresses.{{ $i }}.address_line"
                            label="Street / House / Landmark"
                            placeholder="House no, street, landmark…"
                            rows="2"
                        />

                        <flux:error name="addresses.{{ $i }}.label" />
                        <flux:error name="addresses.{{ $i }}.address_line" />
                        <flux:error name="addresses.{{ $i }}.region_id" />
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700 p-6 text-center">
                        <flux:text size="sm" class="text-zinc-500">No addresses yet.</flux:text>
                    </div>
                @endforelse

                <flux:button size="sm" variant="ghost" icon="plus" wire:click="addAddress" type="button">
                    Add address
                </flux:button>
            </div>
        </section>

        <flux:separator />

        {{-- KYC --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">KYC & Personal</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Identity proofs and date of birth.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
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
                                    :href="route('customer-master.file', ['customer' => $editingId, 'type' => 'aadhar'])"
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
                                    :href="route('customer-master.file', ['customer' => $editingId, 'type' => 'pan'])"
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

                {{-- GST certificate --}}
                <div class="space-y-2">
                    <flux:label>GST Certificate</flux:label>
                    @if ($gst_certificate_file)
                        <flux:file-item
                            :heading="$gst_certificate_file->getClientOriginalName()"
                            :size="$gst_certificate_file->getSize()"
                        >
                            <x-slot name="actions">
                                <flux:file-item.remove wire:click="removeGstCertificateFile" />
                            </x-slot>
                        </flux:file-item>
                    @elseif ($gst_certificate_file_path && $editingId)
                        <flux:file-item :heading="$gst_certificate_file_name ?? 'GST certificate'">
                            <x-slot name="actions">
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    icon="arrow-down-tray"
                                    :href="route('customer-master.file', ['customer' => $editingId, 'type' => 'gst_certificate'])"
                                    target="_blank"
                                />
                                <flux:file-item.remove wire:click="removeGstCertificateFile" />
                            </x-slot>
                        </flux:file-item>
                    @else
                        <flux:file-upload wire:model="gst_certificate_file" accept="image/jpeg,image/png,application/pdf">
                            <flux:file-upload.dropzone
                                heading="Upload GST certificate"
                                text="JPG, PNG, or PDF up to 5 MB"
                            />
                        </flux:file-upload>
                    @endif
                    <flux:error name="gst_certificate_file" />
                </div>

                <flux:date-picker locale="en-IN"
                    wire:model="date_of_birth"
                    label="Date of Birth"
                    placeholder="Select date"
                    with-today
                    selectable-header
                    fixed-weeks
                    type="input"
                />
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
                    placeholder="Anything the team should know about this customer"
                    rows="3"
                />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive customers won't appear in dropdowns on new appointments or job cards."
                />
            </div>
        </section>

        {{-- Sticky bottom action bar --}}
        <div class="sticky bottom-0 -mx-6 lg:-mx-8 px-6 lg:px-8 py-3 mt-4 border-t border-zinc-200 dark:border-zinc-700 bg-white/85 dark:bg-zinc-900/85 backdrop-blur">
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" :href="route('customer-master.index')" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary" icon="check">
                    {{ $editingId ? 'Save changes' : 'Create' }}
                </flux:button>
            </div>
        </div>
    </form>

    {{-- Customer quick-add modal — backed by the CanQuickAddCustomer trait. --}}
    @can('customer_master.create')
        @include('customer-master::_quick_add_modal')
    @endcan

    {{-- Offered right after a create: a new customer nearly always brings a car. --}}
    <flux:modal name="add-vehicle-prompt" class="max-w-md" :dismissible="false">
        <div class="space-y-6">
            <div class="flex items-start gap-3">
                <div class="rounded-full bg-lime-100 dark:bg-lime-900/40 p-2">
                    <flux:icon.check class="size-5 text-lime-600 dark:text-lime-400" />
                </div>
                <div>
                    <flux:heading size="lg">Customer created</flux:heading>
                    <flux:text class="mt-1">
                        Add a vehicle for
                        <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ trim(implode(' ', array_filter([$first_name, $middle_name, $last_name]))) }}</span>{{ $company_name ? ' ('.$company_name.')' : '' }}?
                        They'll already be selected.
                    </flux:text>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" :href="route('customer-master.index')" wire:navigate>
                    Not now
                </flux:button>
                @can('customer_vehicle_master.create')
                    <flux:button variant="primary" icon="truck" wire:click="addVehicle">
                        Add vehicle
                    </flux:button>
                @endcan
            </div>
        </div>
    </flux:modal>

    {{-- Related-areas flyout --}}
    @if ($editingId)
        <livewire:record-panel subject="customer" :record-id="$editingId" :record-label="trim(implode(' ', array_filter([$first_name, $middle_name, $last_name])))" />
    @endif
</div>
