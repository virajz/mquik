<div>
    <flux:modal name="inspection-template-master-form" class="md:w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Inspection Template' : 'New Inspection Template' }}
                </flux:heading>
                <flux:subheading>
                    Reusable inspection checklists for PMS, Tyre, Bodyshop, Basic, and Custom inspections.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input wire:model="name" label="Template Name" placeholder="e.g. PMS STANDARD" required autofocus />
                    </div>
                    <flux:input wire:model="code" label="Code" placeholder="PMS-STD" maxlength="30" class:input="font-mono uppercase tracking-wide" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="applies_to" label="Applies To" variant="listbox" required>
                        @foreach ($appliesToOptions as $opt)
                            <flux:select.option :value="$opt">{{ ucfirst($opt) }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="frequency" label="Inspection Frequency" variant="listbox" clearable placeholder="Optional…">
                        @foreach (\App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster::frequencies() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:field>
                    <flux:label>Items</flux:label>
                    <flux:description>Pick all checkpoints this template should walk a tech through.</flux:description>
                    <flux:select wire:model="selected_item_ids" multiple variant="listbox" searchable placeholder="Select inspection items">
                        @foreach ($availableItems as $item)
                            <flux:select.option :value="$item->id">
                                {{ $item->group?->name ? '['.$item->group->name.'] ' : '' }}{{ $item->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="selected_item_ids" />
                    <flux:error name="selected_item_ids.*" />
                </flux:field>

                <flux:textarea wire:model="notes" label="Notes" placeholder="Anything the team should know about this template" rows="2" />

                <flux:separator variant="subtle" />

                <flux:switch wire:model="is_active" label="Active" description="Inactive templates won't appear in inspection dropdowns." />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
