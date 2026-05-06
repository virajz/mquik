<div>
    <div class="px-3 py-2">
        <flux:input
            wire:model.live.debounce.100ms="search"
            size="sm"
            icon="magnifying-glass"
            placeholder="{{ __('Search menu...') }}"
            clearable
        />
    </div>

    <flux:sidebar.nav>
        @if ($pinnedItems->isNotEmpty())
            <flux:sidebar.group :heading="__('Pinned')" expandable>
                @foreach ($pinnedItems as $i => $item)
                    <div class="group/pin relative">
                        <flux:sidebar.item
                            :icon="$item['icon'] ?? 'cube'"
                            :href="$item['route'] ? route($item['route']) : '#'"
                            :current="$item['route'] && $currentRoute === $item['route']"
                            wire:navigate
                        >
                            {{ __($item['label']) }}
                        </flux:sidebar.item>

                        <div class="absolute right-1 top-1/2 -translate-y-1/2 hidden group-hover/pin:flex items-center gap-0.5 bg-zinc-50 dark:bg-zinc-900 pl-1 rounded">
                            @if ($i > 0)
                                <button type="button"
                                    wire:click.stop="movePin({{ $item['pin_id'] }}, 'up')"
                                    title="Move up"
                                    class="p-0.5 rounded hover:bg-zinc-200 dark:hover:bg-zinc-700">
                                    <flux:icon.chevron-up class="size-3 text-zinc-500" />
                                </button>
                            @endif
                            @if ($i < $pinnedItems->count() - 1)
                                <button type="button"
                                    wire:click.stop="movePin({{ $item['pin_id'] }}, 'down')"
                                    title="Move down"
                                    class="p-0.5 rounded hover:bg-zinc-200 dark:hover:bg-zinc-700">
                                    <flux:icon.chevron-down class="size-3 text-zinc-500" />
                                </button>
                            @endif
                            <button type="button"
                                wire:click.stop="togglePin('{{ $item['route'] }}', '{{ addslashes($item['label']) }}', '{{ $item['icon'] ?? '' }}')"
                                title="Unpin"
                                class="p-0.5 rounded hover:bg-zinc-200 dark:hover:bg-zinc-700">
                                <flux:icon.x-mark class="size-3 text-zinc-500" />
                            </button>
                        </div>
                    </div>
                @endforeach
            </flux:sidebar.group>
        @endif

        @if ($platformItems->isNotEmpty())
            <flux:sidebar.group :heading="__('Platform')" expandable>
                @foreach ($platformItems as $item)
                    <div class="group/pin relative">
                        <flux:sidebar.item
                            :icon="$item['icon']"
                            :href="route($item['route'])"
                            :current="$currentRoute === $item['route']"
                            wire:navigate
                        >
                            {{ __($item['label']) }}
                        </flux:sidebar.item>
                        @unless (in_array($item['route'], $pinnedRouteNames, true))
                            <button type="button"
                                wire:click.stop="togglePin('{{ $item['route'] }}', '{{ addslashes($item['label']) }}', '{{ $item['icon'] ?? '' }}')"
                                title="Pin to top"
                                class="absolute right-2 top-1/2 -translate-y-1/2 hidden group-hover/pin:block p-0.5 rounded hover:bg-zinc-200 dark:hover:bg-zinc-700">
                                <flux:icon.bookmark class="size-3.5 text-zinc-500" />
                            </button>
                        @endunless
                    </div>
                @endforeach
            </flux:sidebar.group>
        @endif

        @foreach ($groups as $group => $items)
            <flux:sidebar.group :heading="__($group)" expandable>
                @foreach ($items as $item)
                    <div class="group/pin relative">
                        <flux:sidebar.item
                            :icon="$item['icon'] ?? 'cube'"
                            :href="$item['route'] ? route($item['route']) : '#'"
                            :current="$item['route'] && $currentRoute === $item['route']"
                            wire:navigate
                        >
                            {{ __($item['label']) }}
                        </flux:sidebar.item>
                        @unless (in_array($item['route'], $pinnedRouteNames, true))
                            <button type="button"
                                wire:click.stop="togglePin('{{ $item['route'] }}', '{{ addslashes($item['label']) }}', '{{ $item['icon'] ?? '' }}')"
                                title="Pin to top"
                                class="absolute right-2 top-1/2 -translate-y-1/2 hidden group-hover/pin:block p-0.5 rounded hover:bg-zinc-200 dark:hover:bg-zinc-700">
                                <flux:icon.bookmark class="size-3.5 text-zinc-500" />
                            </button>
                        @endunless
                    </div>
                @endforeach
            </flux:sidebar.group>
        @endforeach

        @if ($platformItems->isEmpty() && $groups->isEmpty() && $pinnedItems->isEmpty())
            <div class="px-3 py-6 text-center text-sm text-zinc-500">
                No menu items match "{{ $search }}".
            </div>
        @endif
    </flux:sidebar.nav>
</div>
