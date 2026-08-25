<div>
    <form wire:submit="save" novalidate class="max-w-3xl">
        <div class="mb-8">
            <flux:link :href="route('checklist-template-master.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Checklist Templates
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($name ?: 'Edit Checklist Template') : 'New Checklist Template' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Reusable checklists with inline items. Used by document collection, pre-delivery, safety, etc.</flux:text>
        </div>

        <flux:separator />

        {{-- DETAILS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Details</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Name, group and what it applies to.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <flux:input wire:model="name" label="Template Name" placeholder="e.g. DOCUMENT COLLECTION STANDARD" required autofocus />
                    </div>
                    <flux:input wire:model="code" label="Code" placeholder="DOC-STD" maxlength="30" class:input="font-mono uppercase tracking-wide" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="checklist_group_id" label="Group" variant="combobox" required>
                        <x-slot name="input">
                            <flux:select.input wire:model="checklistGroupSearch" placeholder="Pick or type to add…" />
                        </x-slot>
                        @foreach ($groupOptions as $g)
                            <flux:select.option :value="$g->id" wire:key="cg-{{ $g->id }}">{{ $g->name }}</flux:select.option>
                        @endforeach
                        @can('checklist_group_master.create')
                            <flux:select.option.create wire:click="createChecklistGroup" min-length="2">
                                Create "<span wire:text="checklistGroupSearch"></span>"
                            </flux:select.option.create>
                        @endcan
                    </flux:select>

                    <flux:select wire:model="applies_to" label="Applies To" variant="listbox" required>
                        @foreach ($appliesToOptions as $opt)
                            <flux:select.option :value="$opt">{{ ucfirst(str_replace('_', ' ', $opt)) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- ITEMS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Items</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Each row is a checkpoint. Mark required items so they can't be skipped.</flux:text>
            </div>
            <div class="min-w-0">
                <div class="space-y-2">
                    @foreach ($items as $i => $item)
                        <div class="flex items-start gap-2" wire:key="item-row-{{ $i }}">
                            <div class="flex-1">
                                <flux:input wire:model="items.{{ $i }}.label" placeholder="e.g. RC COPY" class:input="uppercase tracking-wide" />
                                <flux:error name="items.{{ $i }}.label" />
                            </div>
                            <div class="pt-2">
                                <flux:checkbox wire:model="items.{{ $i }}.is_required" label="Required" />
                            </div>
                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" />
                        </div>
                    @endforeach
                </div>

                <flux:error name="items" />

                <div class="mt-3">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add item</flux:button>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- STATUS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Notes & Status</flux:heading>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:textarea wire:model="notes" label="Notes" placeholder="Anything the team should know about this template" rows="2" />
                <flux:switch wire:model="is_active" label="Active" description="Inactive templates won't appear in dropdowns." />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('checklist-template-master.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create' }}</flux:button>
        </div>
    </form>
</div>
