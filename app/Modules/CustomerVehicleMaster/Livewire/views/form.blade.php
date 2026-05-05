<div>
    <flux:modal name="customer-vehicle-master-form" class="md:w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Vehicle' : 'New Customer Vehicle' }}</flux:heading>
                <flux:subheading>Link a vehicle to its owner. Used by appointments, job cards, and insurance claims.</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <flux:select wire:model="customer_id" label="Customer" variant="listbox" placeholder="Select customer" searchable required>
                    @foreach ($this->customers as $c)
                        <flux:select.option :value="$c->id">{{ $c->name }} — +91 {{ $c->phone }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="model_id" label="Model" variant="listbox" placeholder="Select model" searchable required>
                        @foreach ($this->models as $m)
                            <flux:select.option :value="$m->id">{{ $m->brand?->name }} {{ $m->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="variant_id" label="Variant" variant="listbox" placeholder="{{ $model_id ? 'Optional' : 'Pick a model first' }}" :disabled="! $model_id">
                        <flux:select.option value="">— Skip —</flux:select.option>
                        @foreach ($this->variants as $v)
                            <flux:select.option :value="$v->id">{{ $v->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input
                        wire:model="registration_no"
                        label="Registration No."
                        placeholder="STATE RTO ALPHA NUMBER"
                        maxlength="20"
                        class:input="font-mono uppercase tracking-wide"
                        required
                    />
                    <flux:select wire:model="color_id" label="Color" variant="listbox" placeholder="Optional">
                        <flux:select.option value="">— Skip —</flux:select.option>
                        @foreach ($this->colors as $c)
                            <flux:select.option :value="$c->id">{{ $c->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <flux:input wire:model="year_of_manufacture" type="number" label="Year" placeholder="2022" min="1980" :max="(int) date('Y') + 1" />
                    <flux:input wire:model="odometer_km" type="number" label="Odometer (km)" placeholder="45000" min="0" />
                    <flux:input wire:model="engine_no" label="Engine No." maxlength="30" class:input="font-mono uppercase tracking-wide" />
                </div>

                <flux:input
                    wire:model="vin"
                    label="VIN / Chassis No."
                    placeholder="17 characters"
                    maxlength="17"
                    class:input="font-mono uppercase tracking-wide"
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:date-picker
                        wire:model="insurance_expiry"
                        label="Insurance Expiry"
                        placeholder="Select date"
                        with-today
                        selectable-header
                        fixed-weeks
                        type="input"
                    />
                    <flux:date-picker
                        wire:model="puc_expiry"
                        label="PUC Expiry"
                        placeholder="Select date"
                        with-today
                        selectable-header
                        fixed-weeks
                        type="input"
                    />
                </div>

                <flux:textarea wire:model="notes" label="Internal Notes" rows="2" />

                <flux:separator variant="subtle" />

                <flux:switch wire:model="is_active" label="Active" description="Inactive vehicles won't appear in appointment / job card dropdowns." />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
