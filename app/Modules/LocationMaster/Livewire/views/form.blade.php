<div>
    <flux:modal name="location-master-form" class="md:w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Location' : 'New Location' }}</flux:heading>
                <flux:subheading>Workshop branches — used by Job Cards, Inventory, Invoicing for branch-level reporting.</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                {{-- IDENTITY --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input
                        wire:model="code"
                        label="Code"
                        placeholder="e.g. SAT"
                        maxlength="20"
                        required
                        class:input="font-mono uppercase tracking-wide"
                    />
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Branch Name"
                            placeholder="e.g. Mquik Satellite Branch"
                            required
                            autofocus
                        />
                    </div>
                </div>

                <flux:switch
                    wire:model="is_head_office"
                    label="Head Office"
                    description="Mark this branch as the head office. Only one head office is recommended per workshop."
                />

                <flux:separator variant="subtle" />

                {{-- ADDRESS --}}
                <flux:textarea wire:model="address" label="Address" rows="2" placeholder="Street, building, landmark" />

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:select wire:model="city_id" label="City" variant="listbox" placeholder="Select city" searchable>
                        <flux:select.option value="">— None —</flux:select.option>
                        @foreach ($cities as $c)
                            <flux:select.option :value="$c->id">{{ $c->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="state_id" label="State" variant="listbox" placeholder="Select state" searchable>
                        <flux:select.option value="">— None —</flux:select.option>
                        @foreach ($states as $s)
                            <flux:select.option :value="$s->id">{{ $s->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="pincode" label="Pincode" mask="999999" inputmode="numeric" maxlength="6" placeholder="380015" />
                </div>

                <flux:separator variant="subtle" />

                {{-- CONTACT --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Phone</flux:label>
                        <flux:input.group>
                            <flux:input.group.prefix>+91</flux:input.group.prefix>
                            <flux:input wire:model="phone" mask="99999 99999" placeholder="98765 43210" inputmode="numeric" />
                        </flux:input.group>
                        <flux:error name="phone" />
                    </flux:field>

                    <flux:input wire:model="email" type="email" label="Email" placeholder="branch@workshop.com" icon="envelope" />
                </div>

                <flux:separator variant="subtle" />

                {{-- TAX --}}
                <flux:input
                    wire:model="gstin"
                    label="GSTIN"
                    placeholder="24ABCDE1234F1Z5"
                    maxlength="15"
                    class:input="font-mono uppercase tracking-wide"
                    description="Each branch typically has its own GSTIN registration."
                />

                <flux:separator variant="subtle" />

                <flux:textarea wire:model="notes" label="Internal Notes" rows="2" />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive locations won't appear in job card / inventory / invoice dropdowns."
                />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
