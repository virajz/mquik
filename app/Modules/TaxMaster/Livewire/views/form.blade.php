<div>
    <flux:modal name="tax-master-form" :dismissible="false" class="md:w-lg">
        <form wire:submit="save" novalidate class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Tax' : 'New Tax' }}
                </flux:heading>
                <flux:subheading>
                    GST/HSN tax codes used by spares, labour, and invoices.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Tax Name"
                            placeholder="e.g. GST 18%"
                            required
                            autofocus
                        />
                    </div>

                    <flux:input
                        wire:model="code"
                        label="Code"
                        placeholder="GST18"
                        maxlength="20"
                        required
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                <flux:input
                    wire:model="hsn_sac"
                    label="HSN/SAC"
                    placeholder="e.g. 8708"
                    maxlength="8"
                    class:input="font-mono uppercase tracking-wide"
                    description="4-8 digit HSN (goods) or SAC (services) code."
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>GST %</flux:label>
                        <flux:input.group>
                            <flux:input wire:model="gst_percent" type="number" step="0.01" min="0" max="50" inputmode="decimal" required />
                            <flux:input.group.suffix>%</flux:input.group.suffix>
                        </flux:input.group>
                        <flux:error name="gst_percent" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Cess %</flux:label>
                        <flux:input.group>
                            <flux:input wire:model="cess_percent" type="number" step="0.01" min="0" max="200" inputmode="decimal" />
                            <flux:input.group.suffix>%</flux:input.group.suffix>
                        </flux:input.group>
                        <flux:error name="cess_percent" />
                    </flux:field>
                </div>

                <flux:textarea
                    wire:model="notes"
                    label="Notes"
                    placeholder="Anything the team should know about this tax"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive taxes won't appear in spare, service, and invoice dropdowns."
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
