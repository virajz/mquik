<div>
    <flux:modal name="region-master-form" :dismissible="false" class="md:w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Region' : 'New Region' }}
                </flux:heading>
                <flux:subheading>
                    Geographic hierarchy used by Customer, Vendor, and Employee addresses.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="kind" label="Kind" variant="listbox" required>
                        <flux:select.option value="state">State</flux:select.option>
                        <flux:select.option value="city">City</flux:select.option>
                        <flux:select.option value="area">Area</flux:select.option>
                        <flux:select.option value="pincode">Pincode</flux:select.option>
                    </flux:select>

                    @if ($kind !== 'state')
                        <flux:select
                            wire:model="parent_id"
                            label="Parent"
                            variant="combobox"
                            required
                        >
                            <x-slot name="input">
                                <flux:select.input wire:model="parentSearch" placeholder="Pick or type to add…" />
                            </x-slot>
                            @foreach ($this->parentOptions as $p)
                                <flux:select.option :value="$p->id" wire:key="rp-{{ $p->id }}">{{ $p->name }}</flux:select.option>
                            @endforeach
                            @can('region_master.create')
                                <flux:select.option.create wire:click="createParent" min-length="2">
                                    Create "<span wire:text="parentSearch"></span>"
                                </flux:select.option.create>
                            @endcan
                        </flux:select>
                    @else
                        <div class="flex items-end text-sm text-zinc-500">
                            States are top-level — no parent.
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Name"
                            placeholder="e.g. GUJARAT / AHMEDABAD / SATELLITE / 380015"
                            required
                            autofocus
                        />
                    </div>
                    <flux:input
                        wire:model="code"
                        label="Code"
                        placeholder="GJ"
                        maxlength="20"
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                <flux:textarea
                    wire:model="notes"
                    label="Notes"
                    placeholder="Anything the team should know about this region"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive regions won't appear in address dropdowns."
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
