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
        @if ($platformItems->isNotEmpty())
            <flux:sidebar.group :heading="__('Platform')" expandable>
                @foreach ($platformItems as $item)
                    <flux:sidebar.item
                        :icon="$item['icon']"
                        :href="route($item['route'])"
                        :current="$currentRoute === $item['route']"
                        wire:navigate
                    >
                        {{ __($item['label']) }}
                    </flux:sidebar.item>
                @endforeach
            </flux:sidebar.group>
        @endif

        @foreach ($groups as $group => $items)
            <flux:sidebar.group :heading="__($group)" expandable>
                @foreach ($items as $item)
                    <flux:sidebar.item
                        :icon="$item['icon'] ?? 'cube'"
                        :href="$item['route'] ? route($item['route']) : '#'"
                        :current="$item['route'] && $currentRoute === $item['route']"
                        wire:navigate
                    >
                        {{ __($item['label']) }}
                    </flux:sidebar.item>
                @endforeach
            </flux:sidebar.group>
        @endforeach

        @if ($platformItems->isEmpty() && $groups->isEmpty())
            <div class="px-3 py-6 text-center text-sm text-zinc-500">
                No menu items match "{{ $search }}".
            </div>
        @endif
    </flux:sidebar.nav>
</div>
