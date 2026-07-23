<div>
    <flux:modal name="inspection-item-master-form" :dismissible="false" class="md:w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Inspection Item' : 'New Inspection Item' }}
                </flux:heading>
                <flux:subheading>
                    Individual checkpoints performed during inspections — Engine Oil Level, Tyre Tread Depth, Brake Pad Thickness, etc.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input wire:model="name" label="Item Name" placeholder="e.g. ENGINE OIL LEVEL" required autofocus />
                    </div>
                    <flux:input wire:model="code" label="Code" placeholder="EOL" maxlength="30" class:input="font-mono uppercase tracking-wide" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="inspection_item_group_id" label="Group" variant="combobox" clearable>
                        <x-slot name="input">
                            <flux:select.input wire:model="inspectionItemGroupSearch" placeholder="Pick or type to add…" />
                        </x-slot>
                        @foreach ($groups as $g)
                            <flux:select.option :value="$g->id" wire:key="iig-{{ $g->id }}">{{ $g->name }}</flux:select.option>
                        @endforeach
                        @can('inspection_item_group_master.create')
                            <flux:select.option.create wire:click="createInspectionItemGroup" min-length="2">
                                Create "<span wire:text="inspectionItemGroupSearch"></span>"
                            </flux:select.option.create>
                        @endcan
                    </flux:select>

                    <flux:select wire:model.live="check_type" label="Check Type" variant="listbox" required>
                        @foreach ($checkTypes as $ct)
                            <flux:select.option :value="$ct">{{ str_replace('_', '/', ucfirst($ct)) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:input
                    wire:model="measurement_unit"
                    label="Measurement Unit"
                    placeholder="e.g. mm, %, bar, V"
                    maxlength="20"
                    description="Only meaningful for Measurement check-type."
                    :disabled="$check_type !== 'measurement'"
                />

                <flux:textarea wire:model="notes" label="Notes" placeholder="Anything the team should know about this checkpoint" rows="2" />

                <flux:separator variant="subtle" />

                <flux:switch wire:model="is_active" label="Active" description="Inactive items won't appear in inspection-template dropdowns." />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
