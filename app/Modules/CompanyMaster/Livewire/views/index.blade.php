<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Company</flux:heading>
            <flux:text class="mt-1">Your workshop's legal entity — appears on invoices, letterheads, and reports.</flux:text>
        </div>
    </div>

    <form wire:submit="save" novalidate class="space-y-5 max-w-4xl">
        {{-- IDENTITY --}}
        <div>
            <flux:heading size="lg">Identity</flux:heading>
            <flux:subheading>How the company is registered and known.</flux:subheading>
        </div>

        <flux:separator variant="subtle" />

        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="md:col-span-2">
                    <flux:input wire:model="legal_name" label="Legal Name" placeholder="e.g. MQUIK AUTO SERVICES PVT LTD" required autofocus />
                </div>
                <flux:input wire:model="code" label="Code" placeholder="e.g. MQUIK" maxlength="20" class:input="font-mono uppercase tracking-wide" />
            </div>

            <flux:input wire:model="trade_name" label="Trade Name" placeholder="e.g. MQUIK WORKSHOP" required />
        </div>

        <flux:separator variant="subtle" />

        {{-- TAX REGISTRATIONS --}}
        <div>
            <flux:heading size="lg">Tax Registrations</flux:heading>
            <flux:subheading>Required on all GST invoices.</flux:subheading>
        </div>

        <flux:separator variant="subtle" />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <flux:input
                wire:model="gstin"
                label="GSTIN"
                placeholder="24ABCDE1234F1Z5"
                maxlength="15"
                class:input="font-mono uppercase tracking-wide"
                description="15-character GST registration number."
            />
            <flux:input
                wire:model="pan"
                label="PAN"
                placeholder="ABCDE1234F"
                maxlength="10"
                class:input="font-mono uppercase tracking-wide"
            />
            <flux:input
                wire:model="cin"
                label="CIN"
                placeholder="U50402GJ2020PTC112233"
                maxlength="21"
                class:input="font-mono uppercase tracking-wide"
                description="Company Identification Number (Pvt Ltd)."
            />
        </div>

        <flux:separator variant="subtle" />

        {{-- ADDRESS --}}
        <div>
            <flux:heading size="lg">Address</flux:heading>
            <flux:subheading>Workshop's registered office address.</flux:subheading>
        </div>

        <flux:separator variant="subtle" />

        <div class="space-y-4">
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
        </div>

        <flux:separator variant="subtle" />

        {{-- CONTACT --}}
        <div>
            <flux:heading size="lg">Contact</flux:heading>
            <flux:subheading>Public-facing contact details.</flux:subheading>
        </div>

        <flux:separator variant="subtle" />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <flux:field>
                <flux:label>Phone</flux:label>
                <flux:input.group>
                    <flux:input.group.prefix>+91</flux:input.group.prefix>
                    <flux:input wire:model="phone" mask="99999 99999" placeholder="98765 43210" inputmode="numeric" />
                </flux:input.group>
                <flux:error name="phone" />
            </flux:field>

            <flux:input wire:model="email" type="email" label="Email" placeholder="admin@workshop.com" icon="envelope" />

            <flux:input wire:model="website" label="Website" placeholder="https://workshop.com" icon="globe-alt" />
        </div>

        <flux:separator variant="subtle" />

        {{-- BRANDING --}}
        <div>
            <flux:heading size="lg">Branding</flux:heading>
            <flux:subheading>Used on PDF invoices, letterheads, and signed documents.</flux:subheading>
        </div>

        <flux:separator variant="subtle" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
                <flux:label>Logo</flux:label>
                @if ($logo_path && ! $logo)
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('storage/' . $logo_path) }}" alt="Logo" class="h-16 w-auto rounded border border-zinc-200 dark:border-zinc-700 bg-white p-2" />
                        <flux:button size="sm" variant="ghost" wire:click="$set('logo_path', null)" icon="trash">Replace</flux:button>
                    </div>
                @endif
                <flux:file-upload wire:model="logo" accept="image/*">
                    @if (! $logo)
                        <flux:file-upload.dropzone
                            heading="Drop your logo here or click to browse"
                            text="PNG, JPG, SVG up to 2 MB"
                        />
                    @else
                        <flux:file-item
                            icon="photo"
                            heading="{{ $logo->getClientOriginalName() }}"
                            size="{{ $logo->getSize() }}"
                        >
                            <flux:file-item.remove wire:click="$set('logo', null)" />
                        </flux:file-item>
                    @endif
                </flux:file-upload>
                @error('logo')
                    <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
                @enderror
            </div>

            <div class="space-y-2">
                <flux:label>Authorised Signature</flux:label>
                @if ($signature_path && ! $signature)
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('storage/' . $signature_path) }}" alt="Signature" class="h-16 w-auto rounded border border-zinc-200 dark:border-zinc-700 bg-white p-2" />
                        <flux:button size="sm" variant="ghost" wire:click="$set('signature_path', null)" icon="trash">Replace</flux:button>
                    </div>
                @endif
                <flux:file-upload wire:model="signature" accept="image/*">
                    @if (! $signature)
                        <flux:file-upload.dropzone
                            heading="Drop the signature image here or click to browse"
                            text="PNG, JPG up to 2 MB"
                        />
                    @else
                        <flux:file-item
                            icon="pencil-square"
                            heading="{{ $signature->getClientOriginalName() }}"
                            size="{{ $signature->getSize() }}"
                        >
                            <flux:file-item.remove wire:click="$set('signature', null)" />
                        </flux:file-item>
                    @endif
                </flux:file-upload>
                @error('signature')
                    <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
                @enderror
            </div>
        </div>

        <flux:separator variant="subtle" />

        {{-- INVOICE DEFAULTS --}}
        <div>
            <flux:heading size="lg">Invoice Defaults</flux:heading>
            <flux:subheading>Footer text shown on every printed invoice.</flux:subheading>
        </div>

        <flux:separator variant="subtle" />

        <flux:textarea wire:model="invoice_footer" label="Invoice Footer" rows="3" placeholder="Thank you for your business. Subject to Ahmedabad jurisdiction." />

        <flux:separator variant="subtle" />

        <flux:switch wire:model="is_active" label="Active" description="Inactive companies won't appear in invoice templates." />

        <div class="flex justify-end pt-2">
            <flux:button type="submit" variant="primary" icon="check">Save Company</flux:button>
        </div>
    </form>
</div>
