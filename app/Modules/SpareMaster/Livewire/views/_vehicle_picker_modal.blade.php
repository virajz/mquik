{{--
    Bulk vehicle-compatibility picker.

    Three levels of granularity, because a part can fit one variant or a whole
    brand: tick the brand, tick a model, or drill into individual variants.

    Note the `wire:key` on each row carries the ticked state. Without it Livewire
    reuses the existing DOM node and the tick never repaints — the server sends
    the right HTML, the browser just doesn't apply it.
--}}
<flux:modal name="vehicle-picker" class="max-w-3xl">
    <div class="space-y-5">
        <div>
            <flux:heading size="lg">Vehicle Compatibility</flux:heading>
            <flux:text class="mt-1">Pick the variants this spare fits. Changes apply as you tick.</flux:text>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <flux:select wire:model.live="pickerBrandId" variant="listbox" searchable clearable placeholder="Brand…">
                @foreach ($this->pickerBrands as $b)
                    <flux:select.option :value="$b->id" wire:key="pb-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="pickerModelId" variant="listbox" searchable clearable placeholder="All models" :disabled="! $pickerBrandId">
                @foreach ($this->pickerModels as $m)
                    <flux:select.option :value="$m->id" wire:key="pm-{{ $m->id }}">{{ $m->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input
                wire:model.live.debounce.300ms="pickerSearch"
                placeholder="Filter variants…"
                icon="magnifying-glass"
                clearable
            />
        </div>

        @if ($pickerBrandId && ! $pickerModelId && $pickerSearch === '')
            {{-- Brand view: whole brand in one click, or a model at a time. --}}
            <div class="flex items-center justify-between gap-2">
                <flux:text size="sm" class="text-zinc-500">{{ count($this->pickerModelSummary) }} model(s)</flux:text>
                <flux:button size="sm" variant="ghost" type="button" icon="check-badge" wire:click="toggleBrand">
                    Select entire brand
                </flux:button>
            </div>

            <div class="max-h-72 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->pickerModelSummary as $m)
                    @php($all = $m['total'] > 0 && $m['selected'] === $m['total'])
                    @php($some = $m['selected'] > 0 && ! $all)
                    <button
                        type="button"
                        wire:click="toggleModel({{ $m['id'] }})"
                        wire:key="pmodel-{{ $m['id'] }}-{{ $m['selected'] }}-{{ $m['total'] }}"
                        class="w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-zinc-50 dark:hover:bg-zinc-800/60 {{ $all ? 'bg-lime-50/60 dark:bg-lime-900/15' : '' }}"
                    >
                        <span class="flex size-4 shrink-0 items-center justify-center rounded border {{ $all ? 'bg-lime-600 border-lime-600' : ($some ? 'border-lime-600' : 'border-zinc-300 dark:border-zinc-600') }}">
                            @if ($all)
                                <flux:icon.check class="size-3 text-white" />
                            @elseif ($some)
                                <span class="size-1.5 rounded-sm bg-lime-600"></span>
                            @endif
                        </span>
                        <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $m['name'] }}</span>
                        <span class="text-xs text-zinc-400 whitespace-nowrap">{{ $m['selected'] }} / {{ $m['total'] }}</span>
                    </button>
                @empty
                    <div class="px-3 py-6 text-center text-sm text-zinc-500">This brand has no active models.</div>
                @endforelse
            </div>
        @elseif ($this->pickerVariants->isNotEmpty())
            <div class="flex items-center justify-between gap-2">
                <flux:text size="sm" class="text-zinc-500">{{ $this->pickerVariants->count() }} variant(s) listed</flux:text>
                <div class="flex gap-2">
                    <flux:button size="sm" variant="ghost" type="button" icon="check" wire:click="selectAllListedVariants">Select all listed</flux:button>
                    <flux:button size="sm" variant="ghost" type="button" icon="x-mark" wire:click="clearListedVariants">Unselect listed</flux:button>
                </div>
            </div>

            <div class="max-h-72 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($this->pickerVariants as $v)
                    @php($isOn = in_array((int) $v->id, array_map('intval', $variant_ids), true))
                    <button
                        type="button"
                        wire:click="toggleVariant({{ $v->id }})"
                        wire:key="pv-{{ $v->id }}-{{ $isOn ? 1 : 0 }}"
                        class="w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-zinc-50 dark:hover:bg-zinc-800/60 {{ $isOn ? 'bg-lime-50/60 dark:bg-lime-900/15' : '' }}"
                    >
                        <span class="flex size-4 shrink-0 items-center justify-center rounded border {{ $isOn ? 'bg-lime-600 border-lime-600' : 'border-zinc-300 dark:border-zinc-600' }}">
                            @if ($isOn)<flux:icon.check class="size-3 text-white" />@endif
                        </span>
                        <span class="min-w-0 flex-1 truncate text-sm">
                            {{ $v->name }}
                            <span class="text-zinc-400">· {{ $v->model?->name }}{{ $v->year ? ' · '.$v->year : '' }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
        @else
            <div class="rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-8 text-center">
                <flux:icon.truck class="mx-auto mb-2 size-7 text-zinc-400" />
                <flux:text class="text-zinc-500">
                    {{ $pickerBrandId ? 'No variants match that filter.' : 'Pick a brand to start.' }}
                </flux:text>
            </div>
        @endif

        <div class="flex items-center justify-between gap-2 pt-1">
            <flux:button size="sm" variant="ghost" type="button" icon="trash" wire:click="clearAllVariants" :disabled="! count($variant_ids)">
                Clear all
            </flux:button>
            <flux:modal.close>
                <flux:button variant="primary" type="button" icon="check">Done</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>
