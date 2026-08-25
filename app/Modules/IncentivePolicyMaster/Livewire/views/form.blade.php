<div>
    <flux:modal name="incentive-policy-master-form" :dismissible="false" class="md:w-md">
        <form wire:submit="save" novalidate class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Incentive Policy' : 'New Incentive Policy' }}
                </flux:heading>
                <flux:subheading>
                    Incentive policies - labour sales, parts sales, satisfaction, efficiency.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Name"
                            placeholder="e.g. LABOUR SALES"
                            required
                            autofocus
                        />
                    </div>

                    <flux:input
                        wire:model="code"
                        label="Code"
                        placeholder="LAB"
                        maxlength="20"
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="basis" variant="listbox" label="Basis">
                        @foreach (\App\Modules\IncentivePolicyMaster\Models\IncentivePolicyMaster::bases() as $k => $l)
                            <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input type="number" step="0.01" min="0" max="100" wire:model="rate_percent" label="Rate %" />
                </div>

                <flux:textarea
                    wire:model="notes"
                    label="Notes"
                    placeholder="Anything the team should know about this policy"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive policies won't appear in dropdowns."
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
