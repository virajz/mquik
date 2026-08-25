<div>
    <flux:modal name="inventory-group-master-form" :dismissible="false" class="md:w-md">
        <form wire:submit="save" novalidate class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Group' : 'New Group' }}
                </flux:heading>
                <flux:subheading>
                    Categories used by Spare and Labour catalogs — Brake, Suspension, Filters, etc. Sub-groups allowed.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="name"
                            label="Group Name"
                            placeholder="e.g. BRAKE"
                            required
                            autofocus
                        />
                    </div>

                    <flux:input
                        wire:model="code"
                        label="Code"
                        placeholder="BRK"
                        maxlength="20"
                        class:input="font-mono uppercase tracking-wide"
                    />
                </div>

                <flux:select wire:model="parent_id" label="Parent Group" variant="combobox" clearable :filter="false">
                <x-slot name="search">
                    <flux:select.search wire:model.live.debounce.250ms="parentSearch" placeholder="Type a group name…" />
                </x-slot>
                    <x-slot name="input">
                        <flux:select.input wire:model="parentSearch" placeholder="Pick or type to add…" />
                    </x-slot>
                    @foreach ($this->parents as $p)
                        <flux:select.option :value="$p->id" wire:key="ig-{{ $p->id }}">{{ $p->name }}</flux:select.option>
                    @endforeach
                    @can('inventory_group_master.create')
                        <flux:select.option.create wire:click="createParent" min-length="2">
                            Create "<span wire:text="parentSearch"></span>"
                        </flux:select.option.create>
                    @endcan
                </flux:select>

                <flux:textarea
                    wire:model="notes"
                    label="Notes"
                    placeholder="Anything the team should know about this group"
                    rows="2"
                />

                <flux:separator variant="subtle" />

                <flux:switch
                    wire:model="is_active"
                    label="Active"
                    description="Inactive groups won't appear in spare and labour catalog dropdowns."
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
