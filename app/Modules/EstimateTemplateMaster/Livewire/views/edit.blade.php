<div>
    <form wire:submit="save" class="max-w-4xl">
        <div class="mb-6">
            <flux:link :href="route('estimate-template-master.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Estimate Templates
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? 'Edit Template' : 'New Estimate Template' }}</flux:heading>
        </div>

        <flux:separator class="mb-6" />

        <div class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <flux:input wire:model="name" label="Template Name" placeholder="e.g. PMS BASIC" required />
                </div>
                <flux:input wire:model="code" label="Code" placeholder="PMS-B" class:input="font-mono uppercase" />
            </div>

            <flux:select wire:model="inventory_group_id" variant="listbox" searchable clearable label="Inventory Group" placeholder="Group-wise (optional)…" class="md:max-w-sm">
                @foreach ($this->inventoryGroups as $g)
                    <flux:select.option :value="$g->id" wire:key="ig-{{ $g->id }}">{{ $g->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:switch wire:model="is_active" label="Active" description="Inactive templates are hidden from the estimate's template picker." />

            <flux:separator variant="subtle" />

            <div class="flex items-center justify-between">
                <flux:heading size="sm">Template Lines</flux:heading>
                <div class="flex gap-2">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem('spare')">Spare</flux:button>
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem('labour')">Labour</flux:button>
                </div>
            </div>

            @if (count($items) === 0)
                <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                    Add spare and labour lines that this template should pre-fill.
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($items as $i => $item)
                        <div wire:key="tmpl-item-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[110px_1fr_120px_auto] gap-2 items-end p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                            <flux:badge size="sm" :color="$item['line_type'] === 'labour' ? 'purple' : 'sky'" class="mb-2">{{ ucfirst($item['line_type']) }}</flux:badge>
                            @if ($item['line_type'] === 'spare')
                                <flux:select wire:model="items.{{ $i }}.spare_id" variant="listbox" searchable size="sm" label="Spare" placeholder="Pick a spare…">
                                    @foreach ($this->spares as $s)
                                        <flux:select.option :value="$s->id" wire:key="ts-{{ $i }}-{{ $s->id }}">{{ $s->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @else
                                <flux:select wire:model="items.{{ $i }}.labour_id" variant="listbox" searchable size="sm" label="Labour" placeholder="Pick a labour…">
                                    @foreach ($this->labours as $l)
                                        <flux:select.option :value="$l->id" wire:key="tl-{{ $i }}-{{ $l->id }}">{{ $l->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @endif
                            <flux:input type="number" step="0.01" min="0.01" wire:model="items.{{ $i }}.default_qty" size="sm" label="Default Qty" />
                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" />
                        </div>
                    @endforeach
                </div>
            @endif

            <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Optional notes about this template." />
        </div>

        <flux:separator class="mt-6" />
        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('estimate-template-master.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save Changes' : 'Create Template' }}</flux:button>
        </div>
    </form>
</div>
