<div>
    <flux:modal name="vendor-master-form" class="md:w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Vendor' : 'New Vendor' }}</flux:heading>
                <flux:subheading>Vendors supply parts, labour, services, and insurance — used by purchase orders and bill entry.</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                {{-- IDENTITY --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input wire:model="vendor_code" label="Vendor Code" placeholder="VND-00001" required class:input="font-mono uppercase tracking-wide" />
                    <div class="md:col-span-2">
                        <flux:input wire:model="name" label="Name" placeholder="Vendor name" required autofocus />
                    </div>
                </div>

                <flux:select wire:model="vendor_type_id" label="Vendor Type" variant="listbox" placeholder="Select type" searchable required>
                    @foreach ($vendorTypes as $t)
                        <flux:select.option :value="$t->id">{{ $t->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:separator variant="subtle" />

                {{-- CONTACT --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Phone <span class="text-red-500">*</span></flux:label>
                        <flux:input.group>
                            <flux:input.group.prefix>+91</flux:input.group.prefix>
                            <flux:input wire:model="phone" mask="99999 99999" placeholder="98765 43210" inputmode="numeric" required />
                        </flux:input.group>
                        <flux:error name="phone" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Alternate Phone</flux:label>
                        <flux:input.group>
                            <flux:input.group.prefix>+91</flux:input.group.prefix>
                            <flux:input wire:model="alternate_phone" mask="99999 99999" placeholder="98765 43210" inputmode="numeric" />
                        </flux:input.group>
                    </flux:field>
                </div>

                <flux:input wire:model="email" type="email" label="Email" placeholder="vendor@example.com" icon="envelope" />

                <flux:textarea wire:model="address" label="Address" rows="2" />

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input wire:model="city" label="City" placeholder="e.g. AHMEDABAD" />
                    <flux:input wire:model="state" label="State" placeholder="e.g. GUJARAT" />
                    <flux:input wire:model="pincode" label="Pincode" mask="999999" inputmode="numeric" maxlength="6" />
                </div>

                <flux:separator variant="subtle" />

                {{-- KYC --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input wire:model="pan" label="PAN" placeholder="ABCDE1234F" maxlength="10" class:input="font-mono uppercase tracking-wide" />
                    <flux:input wire:model="gstin" label="GSTIN" placeholder="22ABCDE1234F1Z5" maxlength="15" class:input="font-mono uppercase tracking-wide" />
                </div>

                <flux:separator variant="subtle" />

                {{-- BANKING --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input wire:model="bank_name" label="Bank Name" placeholder="e.g. HDFC BANK" />
                    <flux:input wire:model="bank_branch" label="Branch" placeholder="e.g. SATELLITE" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input wire:model="ifsc" label="IFSC" placeholder="HDFC0000001" maxlength="11" class:input="font-mono uppercase tracking-wide" />
                    <flux:input wire:model="account_no" label="Account No" placeholder="Account number" class:input="font-mono tracking-wide" />
                    <flux:input wire:model="account_holder" label="Account Holder" placeholder="Name on account" />
                </div>

                <flux:separator variant="subtle" />

                {{-- CREDIT TERMS --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
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
                    <flux:input wire:model="payment_terms" label="Payment Terms" placeholder="e.g. NET 30 / ADVANCE" />
                </div>

                <flux:textarea wire:model="notes" label="Internal Notes" rows="2" />

                <flux:separator variant="subtle" />

                <flux:switch wire:model="is_active" label="Active" description="Inactive vendors won't appear in purchase order or bill entry dropdowns." />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
