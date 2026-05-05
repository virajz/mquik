<div>
    <flux:modal name="customer-master-form" class="md:w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Customer' : 'New Customer' }}
                </flux:heading>
                <flux:subheading>
                    {{ $editingId
                        ? 'Update the customer details below.'
                        : 'Add a customer who books services or buys parts.' }}
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            {{-- IDENTITY --}}
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Name"
                            placeholder="Customer name"
                            required
                            autofocus
                        />
                    </div>
                    <flux:select wire:model="customer_type" variant="listbox" label="Type">
                        <flux:select.option value="walking">Walking</flux:select.option>
                        <flux:select.option value="loyal">Loyal</flux:select.option>
                        <flux:select.option value="corporate">Corporate</flux:select.option>
                    </flux:select>
                </div>

                {{-- CONTACT --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
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

                {{-- ADDRESS --}}
                <flux:textarea
                    wire:model="address"
                    label="Address"
                    placeholder="House / street / area"
                    rows="2"
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input
                        wire:model="city"
                        label="City"
                        placeholder="e.g. AHMEDABAD"
                    />
                    <flux:input
                        wire:model="pincode"
                        label="Pincode"
                        placeholder="380015"
                        mask="999999"
                        inputmode="numeric"
                        maxlength="6"
                    />
                </div>

                <flux:separator variant="subtle" />

                {{-- KYC --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
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

                <flux:textarea
                    wire:model="notes"
                    label="Internal Notes"
                    placeholder="Anything the team should know about this customer"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive customers won't appear in dropdowns on new appointments or job cards."
                />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">
                    {{ $editingId ? 'Save changes' : 'Create' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
