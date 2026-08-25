@use(App\Modules\Consumable\Models\Consumable)
<div class="max-w-7xl">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:link :href="route('consumable.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Consumables
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? 'Consumable '.$consumable_no : 'New Consumable' }}</flux:heading>
        </div>
        @if ($editingId)
            @php($t = $this->totals)
            <flux:badge size="lg" color="zinc" class="font-mono">₹ {{ number_format($t['total'], 2) }}</flux:badge>
        @endif
    </div>

    <flux:separator class="mb-6" />

    <form wire:submit="save" novalidate>
        @if (! $editingId)
            @include('consumable::partials.section-details')
            <flux:callout class="mt-6" icon="information-circle" variant="secondary">
                <flux:callout.text>Create the entry first — line items unlock once it exists. Saving deducts spare-linked lines from stock.</flux:callout.text>
            </flux:callout>
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('consumable.index')" variant="ghost" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Create Consumable</flux:button>
            </div>
        @else
            <flux:tab.group>
                <flux:tabs wire:model="activeTab">
                    <flux:tab name="details" icon="clipboard-document-list">Details</flux:tab>
                    <flux:tab name="items" icon="list-bullet">Items <flux:badge size="sm" class="ml-1">{{ count($items) }}</flux:badge></flux:tab>
                </flux:tabs>

                <flux:tab.panel name="details" class="pt-6">
                    @include('consumable::partials.section-details')
                </flux:tab.panel>

                <flux:tab.panel name="items" class="pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="lg">Consumed Items</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Spare-linked lines deduct from stock; labour lines record labour loss.</flux:text>
                        </div>
                        <div class="flex gap-2">
                            <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addLine('spare')">Spare</flux:button>
                            <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addLine('labour')">Labour</flux:button>
                        </div>
                    </div>

                    @if (count($items) === 0)
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                            Add the consumed spares / labour.
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach ($items as $i => $item)
                                @php($lt = (float) ($item['qty'] ?? 0) * (float) ($item['unit_rate'] ?? 0) * (1 + (float) ($item['tax_percent'] ?? 0) / 100))
                                <div wire:key="cn-line-{{ $i }}" class="rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 space-y-2">
                                    <div class="grid grid-cols-1 lg:grid-cols-[90px_1fr_130px_auto] gap-2 items-end">
                                        <flux:badge size="sm" :color="$item['line_type'] === 'labour' ? 'purple' : 'sky'" class="mb-2">{{ ucfirst($item['line_type']) }}</flux:badge>
                                        @if ($item['line_type'] === 'spare')
                                            <flux:select wire:model="items.{{ $i }}.spare_id" variant="listbox" searchable clearable size="sm" label="Spare" placeholder="Pick…">
                                                @foreach ($this->spares as $s)
                                                    <flux:select.option :value="$s->id" wire:key="sp-{{ $i }}-{{ $s->id }}">{{ $s->name }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        @else
                                            <flux:select wire:model="items.{{ $i }}.labour_id" variant="listbox" searchable clearable size="sm" label="Labour" placeholder="Pick…">
                                                @foreach ($this->labours as $l)
                                                    <flux:select.option :value="$l->id" wire:key="lb-{{ $i }}-{{ $l->id }}">{{ $l->name }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        @endif
                                        <flux:select wire:model="items.{{ $i }}.uom_id" variant="listbox" clearable size="sm" label="UOM">
                                            @foreach ($this->uoms as $u)
                                                <flux:select.option :value="$u->id" wire:key="um-{{ $i }}-{{ $u->id }}">{{ $u->name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeLine({{ $i }})" />
                                    </div>

                                    <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Description" />

                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 items-end">
                                        <flux:input type="number" step="0.01" min="0.01" wire:model="items.{{ $i }}.qty" size="sm" label="Qty" />
                                        <flux:input type="number" step="0.01" min="0" wire:model="items.{{ $i }}.unit_rate" size="sm" label="Rate" />
                                        <flux:input type="number" step="0.01" min="0" max="100" wire:model="items.{{ $i }}.tax_percent" size="sm" label="Tax %" />
                                        <div class="text-right text-sm font-mono whitespace-nowrap pb-1"><span class="text-zinc-400 text-xs">Line</span> {{ number_format($lt, 2) }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @php($t = $this->totals)
                        <div class="mt-4 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/30 p-4 space-y-1.5 text-sm max-w-sm ml-auto">
                            <div class="flex justify-between"><span class="text-zinc-500">Parts</span><span class="font-mono">{{ number_format($t['parts'], 2) }}</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">Labour</span><span class="font-mono">{{ number_format($t['labour'], 2) }}</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">Tax</span><span class="font-mono">{{ number_format($t['tax'], 2) }}</span></div>
                            <flux:separator variant="subtle" class="my-1" />
                            <div class="flex justify-between text-base font-semibold"><span>Total Value</span><span class="font-mono">{{ number_format($t['total'], 2) }}</span></div>
                        </div>
                    @endif
                </flux:tab.panel>
            </flux:tab.group>

            <flux:separator class="mt-4" />
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('consumable.index')" variant="ghost" wire:navigate>Close</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Save Changes</flux:button>
            </div>
        @endif
    </form>
</div>
