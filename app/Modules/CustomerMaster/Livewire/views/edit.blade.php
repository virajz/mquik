<div>
    <form wire:submit="save" class="max-w-4xl">
        {{-- Page header --}}
        <div class="mb-8">
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

        <flux:separator />

        {{-- IDENTITY --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Identity</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">
                    Who they are. For company customers, put the full company name in First Name.
                </flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input
                        wire:model="first_name"
                        label="First Name"
                        placeholder="First or company name"
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="business_type_id" variant="combobox" label="Type" required>
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

                    <flux:select
                        wire:model="referred_by_customer_id"
                        variant="listbox"
                        searchable
                        label="Referred by"
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

                <flux:input
                    wire:model="email"
                    type="email"
                    label="Email"
                    placeholder="customer@example.com"
                    icon="envelope"
                />
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
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="aadhar"
                            label="Aadhar"
                            mask="9999 9999 9999"
                            placeholder="0000 0000 0000"
                            class:input="font-mono uppercase tracking-wide"
                            inputmode="numeric"
                        />
                    </div>
                    <flux:input
                        wire:model="pan"
                        label="PAN"
                        placeholder="ABCDE1234F"
                        maxlength="10"
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                <flux:date-picker
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
</div>
