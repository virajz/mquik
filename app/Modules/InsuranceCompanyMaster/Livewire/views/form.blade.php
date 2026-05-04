<div>
    <flux:modal name="insurance-company-master-form" class="md:w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Insurance Company' : 'New Insurance Company' }}
                </flux:heading>
                <flux:subheading>
                    {{ $editingId
                        ? 'Update the insurance company details below.'
                        : 'Add an insurer this workshop deals with.' }}
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                {{-- Name + short name --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Company Name"
                            placeholder="Insurer name"
                            required
                            autofocus
                        />
                    </div>
                    <flux:input
                        wire:model="short_name"
                        label="Short Name"
                        placeholder="e.g. NIA"
                        maxlength="20"
                    />
                </div>

                {{-- GSTIN + Pass % --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="gstin"
                            label="GSTIN"
                            mask="99aaaaa9999a9z*"
                            placeholder="22AAAAA0000A1Z5"
                            maxlength="15"
                            class:input="font-mono uppercase tracking-wide"
                        />
                    </div>

                    <flux:field>
                        <flux:label>Default Pass %</flux:label>
                        <flux:input
                            wire:model="default_pass_percent"
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            placeholder="100"
                            required
                        />
                        <flux:error name="default_pass_percent" />
                    </flux:field>
                </div>

                {{-- Contact + Phone --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input
                        wire:model="contact_person"
                        label="Contact Person"
                        placeholder="Contact name"
                        icon="user"
                    />

                    <flux:field>
                        <flux:label>Phone</flux:label>
                        <flux:input.group>
                            <flux:input.group.prefix>+91</flux:input.group.prefix>
                            <flux:input
                                wire:model="phone"
                                mask="99999 99999"
                                placeholder="98765 43210"
                                inputmode="numeric"
                            />
                        </flux:input.group>
                        <flux:error name="phone" />
                    </flux:field>
                </div>

                <flux:input
                    wire:model="email"
                    type="email"
                    label="Email"
                    placeholder="claims@insurer.com"
                    icon="envelope"
                />

                <flux:textarea
                    wire:model="address"
                    label="Address"
                    placeholder="Full office address"
                    rows="2"
                />

                <flux:textarea
                    wire:model="notes"
                    label="Internal Notes"
                    placeholder="Anything the team should know about working with this insurer"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive insurers won't appear in dropdowns on new claims."
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
