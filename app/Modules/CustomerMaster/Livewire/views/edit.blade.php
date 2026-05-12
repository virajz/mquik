<div
    x-data="{
        active: 'identity',
        sections: ['identity', 'contact', 'addresses', 'kyc', 'notes'],
        init() {
            const observer = new IntersectionObserver(
                entries => {
                    const visible = entries
                        .filter(e => e.isIntersecting)
                        .sort((a, b) => b.intersectionRatio - a.intersectionRatio);
                    if (visible[0]) this.active = visible[0].target.id;
                },
                { rootMargin: '-30% 0px -55% 0px', threshold: [0, 0.25, 0.5, 0.75, 1] },
            );
            this.sections.forEach(id => {
                const el = document.getElementById(id);
                if (el) observer.observe(el);
            });
        },
    }"
>
    <form wire:submit="save">
        {{-- Header --}}
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <flux:link :href="route('customer-master.index')" variant="ghost" class="text-xs">
                    <flux:icon.chevron-left class="inline size-3 -mt-0.5" />
                    Customers
                </flux:link>
                <flux:heading size="xl" level="1" class="mt-1">
                    {{ $editingId ? $name : 'New Customer' }}
                </flux:heading>
                <flux:text class="mt-1">
                    {{ $editingId
                        ? 'Update the customer details below.'
                        : 'Add a customer who books services or buys parts.' }}
                </flux:text>
            </div>

            <div class="flex items-center gap-2">
                <flux:button variant="ghost" :href="route('customer-master.index')" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary" icon="check">
                    {{ $editingId ? 'Save changes' : 'Create' }}
                </flux:button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_220px] gap-8">
            {{-- Form sections --}}
            <div class="space-y-10 min-w-0">
                {{-- IDENTITY --}}
                <section id="identity" class="scroll-mt-24 space-y-4">
                    <div>
                        <flux:heading size="lg">Identity</flux:heading>
                        <flux:text size="sm" class="text-zinc-500">Who they are.</flux:text>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <flux:input
                                wire:model="name"
                                label="Name"
                                placeholder="Customer name"
                                required
                                autofocus
                            />
                        </div>
                        <flux:select wire:model="business_type_id" variant="listbox" searchable label="Type" required>
                            @foreach ($businessTypes as $bt)
                                <flux:select.option :value="$bt->id">{{ $bt->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </section>

                <flux:separator variant="subtle" />

                {{-- CONTACT --}}
                <section id="contact" class="scroll-mt-24 space-y-4">
                    <div>
                        <flux:heading size="lg">Contact</flux:heading>
                        <flux:text size="sm" class="text-zinc-500">How to reach them.</flux:text>
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

                    <flux:input
                        wire:model="email"
                        type="email"
                        label="Email"
                        placeholder="customer@example.com"
                        icon="envelope"
                    />
                </section>

                <flux:separator variant="subtle" />

                {{-- ADDRESSES --}}
                <section id="addresses" class="scroll-mt-24 space-y-4">
                    <div class="flex items-end justify-between gap-2">
                        <div>
                            <flux:heading size="lg">Addresses</flux:heading>
                            <flux:text size="sm" class="text-zinc-500">
                                Add one or more. The primary is used for pickup, delivery and reports.
                            </flux:text>
                        </div>
                        <flux:button size="sm" variant="ghost" icon="plus" wire:click="addAddress" type="button">
                            Add address
                        </flux:button>
                    </div>

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
                                        variant="listbox"
                                        searchable
                                        label="Region"
                                        placeholder="Search city, area, or pincode…"
                                        clearable
                                    >
                                        @foreach ($this->regions as $r)
                                            <flux:select.option :value="$r['id']">
                                                {{ $r['name'] }}{{ $r['chain'] ? ' — '.$r['chain'] : '' }} ({{ ucfirst($r['kind']) }})
                                            </flux:select.option>
                                        @endforeach
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
                            <flux:button size="sm" variant="ghost" icon="plus" wire:click="addAddress" type="button" class="mt-2">
                                Add an address
                            </flux:button>
                        </div>
                    @endforelse
                </section>

                <flux:separator variant="subtle" />

                {{-- KYC --}}
                <section id="kyc" class="scroll-mt-24 space-y-4">
                    <div>
                        <flux:heading size="lg">KYC & Personal</flux:heading>
                        <flux:text size="sm" class="text-zinc-500">Identity proofs and date of birth.</flux:text>
                    </div>

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
                </section>

                <flux:separator variant="subtle" />

                {{-- NOTES & STATUS --}}
                <section id="notes" class="scroll-mt-24 space-y-4">
                    <div>
                        <flux:heading size="lg">Notes & Status</flux:heading>
                        <flux:text size="sm" class="text-zinc-500">Internal context and visibility.</flux:text>
                    </div>

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
                </section>

                <flux:separator variant="subtle" />

                {{-- Footer actions (mirror header) --}}
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" :href="route('customer-master.index')" wire:navigate>
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary" icon="check">
                        {{ $editingId ? 'Save changes' : 'Create' }}
                    </flux:button>
                </div>
            </div>

            {{-- Right rail: section nav --}}
            <aside class="hidden lg:block">
                <div class="sticky top-6 space-y-3">
                    <flux:text size="sm" class="font-medium text-zinc-500 uppercase tracking-wide text-xs">
                        On this page
                    </flux:text>
                    <nav class="flex flex-col text-sm">
                        @foreach ([
                            'identity'  => 'Identity',
                            'contact'   => 'Contact',
                            'addresses' => 'Addresses',
                            'kyc'       => 'KYC & Personal',
                            'notes'     => 'Notes & Status',
                        ] as $id => $label)
                            <a
                                href="#{{ $id }}"
                                @click.prevent="document.getElementById('{{ $id }}').scrollIntoView({ behavior: 'smooth', block: 'start' })"
                                class="border-l-2 pl-3 py-1.5 transition-colors"
                                :class="active === '{{ $id }}'
                                    ? 'border-zinc-900 text-zinc-900 font-medium dark:border-white dark:text-white'
                                    : 'border-transparent text-zinc-500 hover:text-zinc-900 dark:hover:text-white'"
                            >{{ $label }}</a>
                        @endforeach
                    </nav>
                </div>
            </aside>
        </div>
    </form>
</div>
