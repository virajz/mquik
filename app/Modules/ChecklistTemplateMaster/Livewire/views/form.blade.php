<div>
    <flux:modal name="checklist-template-master-form" class="md:w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Checklist Template' : 'New Checklist Template' }}
                </flux:heading>
                <flux:subheading>
                    Reusable checklists with inline items. Used by document collection, pre-delivery, safety, etc.
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input wire:model="name" label="Template Name" placeholder="e.g. DOCUMENT COLLECTION STANDARD" required autofocus />
                    </div>
                    <flux:input wire:model="code" label="Code" placeholder="DOC-STD" maxlength="30" class:input="font-mono uppercase tracking-wide" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="checklist_group_id" label="Group" variant="listbox" searchable required placeholder="Select a group">
                        @foreach ($groupOptions as $g)
                            <flux:select.option :value="$g->id">{{ $g->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="applies_to" label="Applies To" variant="listbox" required>
                        @foreach ($appliesToOptions as $opt)
                            <flux:select.option :value="$opt">{{ ucfirst(str_replace('_', ' ', $opt)) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <flux:label>Items</flux:label>
                    <flux:description>Each row is a checkpoint. Mark required items so they can't be skipped.</flux:description>

                    <div class="mt-2 space-y-2">
                        @foreach ($items as $i => $item)
                            <div class="flex items-start gap-2" wire:key="item-row-{{ $i }}">
                                <div class="flex-1">
                                    <flux:input
                                        wire:model="items.{{ $i }}.label"
                                        placeholder="e.g. RC COPY"
                                        class:input="uppercase tracking-wide"
                                    />
                                    <flux:error name="items.{{ $i }}.label" />
                                </div>
                                <div class="pt-2">
                                    <flux:checkbox
                                        wire:model="items.{{ $i }}.is_required"
                                        label="Required"
                                    />
                                </div>
                                <flux:button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="removeItem({{ $i }})"
                                />
                            </div>
                        @endforeach
                    </div>

                    <flux:error name="items" />

                    <div class="mt-3">
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">
                            Add item
                        </flux:button>
                    </div>
                </div>

                <flux:textarea wire:model="notes" label="Notes" placeholder="Anything the team should know about this template" rows="2" />

                <flux:separator variant="subtle" />

                <flux:switch wire:model="is_active" label="Active" description="Inactive templates won't appear in dropdowns." />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
