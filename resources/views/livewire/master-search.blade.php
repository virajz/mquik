<div>
    <flux:modal name="master-search" variant="bare" class="md:w-2xl">
        <flux:command @close="$wire.set('term', '')" :filter="false" class="shadow-2xl bg-white dark:bg-zinc-800">
            <flux:command.input wire:model.live.debounce.150ms="term"
                placeholder="Search customers, vehicles, employees, brands..." closable />

            @if (trim($term) === '')
                <div class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    Start typing to search across the workshop.
                </div>
            @elseif ($totalCount === 0)
                <div class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    No matches for <span
                        class="font-medium text-zinc-700 dark:text-zinc-200">"{{ $term }}"</span>.
                </div>
            @else
                @foreach ($results as $group)
                    <div
                        class="px-3 pt-3 pb-1 flex items-center gap-2 text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">
                        <flux:icon name="{{ $group['icon'] }}" class="size-3.5" />
                        <span>{{ $group['label'] }}</span>
                        <span class="text-zinc-300 dark:text-zinc-600">·</span>
                        <span class="text-zinc-400 dark:text-zinc-500">{{ $group['count'] }}</span>
                    </div>
                    <flux:command.items>
                        @foreach ($group['rows'] as $row)
                            <flux:command.item :icon="$group['icon']"
                                :href="$group['route'] ? route($group['route']) : '#'" wire:navigate
                                x-on:click="$flux.modal('master-search').close()">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium">{{ $row['title'] }}</span>
                                    @if (!empty($row['subtitle']))
                                        <span
                                            class="text-xs text-zinc-500 dark:text-zinc-400">{{ $row['subtitle'] }}</span>
                                    @endif
                                </div>
                            </flux:command.item>
                        @endforeach
                    </flux:command.items>
                @endforeach
            @endif
        </flux:command>
    </flux:modal>
</div>
