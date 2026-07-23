<div>
    <flux:modal name="salary-component-master-form" :dismissible="false" class="md:w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Salary Component' : 'New Salary Component' }}
                </flux:heading>
                <flux:subheading>
                    Salary earnings & deductions - basic, HRA, PF, ESI, TDS etc.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Name"
                            placeholder="e.g. BASIC SALARY"
                            required
                            autofocus
                        />
                    </div>

                    <flux:input
                        wire:model="code"
                        label="Code"
                        placeholder="BASIC"
                        maxlength="20"
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="component_type" variant="listbox" label="Type">
                        @foreach (\App\Modules\SalaryComponentMaster\Models\SalaryComponentMaster::componentTypes() as $k => $l)
                            <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="calc_method" variant="listbox" label="Calc Method">
                        @foreach (\App\Modules\SalaryComponentMaster\Models\SalaryComponentMaster::calcMethods() as $k => $l)
                            <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-center">
                    <flux:input type="number" step="0.01" min="0" wire:model="default_value" label="Default Value" description="Amount, or % if '% of Basic'" />
                    <flux:switch wire:model="is_taxable" label="Taxable" description="Counts toward taxable income." />
                </div>

                <flux:textarea
                    wire:model="notes"
                    label="Notes"
                    placeholder="Anything the team should know about this component"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive components won't appear in dropdowns."
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
