<div>
    <flux:modal name="record-panel" variant="flyout" class="w-full max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Related Areas</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">
                    Everything linked to {{ $recordLabel ?: 'this record' }}. Each opens filtered to it.
                </flux:text>
            </div>

            <div class="space-y-2">
                @forelse ($this->areas as $area)
                    <a href="{{ $area['url'] }}" target="_blank"
                        class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 transition hover:border-mq-orange-500 hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                        <span class="flex min-w-0 items-center gap-3">
                            <flux:icon :name="$area['icon']" class="size-5 shrink-0 text-zinc-400" />
                            <span class="truncate text-sm font-medium">{{ $area['label'] }}</span>
                        </span>
                        <span class="flex shrink-0 items-center gap-2">
                            <flux:badge size="sm" :color="$area['count'] > 0 ? 'lime' : 'zinc'">{{ $area['count'] }}</flux:badge>
                            <flux:icon.arrow-top-right-on-square class="size-4 text-zinc-400" />
                        </span>
                    </a>
                @empty
                    <div class="py-10 text-center">
                        <flux:icon.squares-2x2 class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                        <flux:heading size="lg" class="mt-3">Nothing linked yet</flux:heading>
                        <flux:text size="sm" class="mt-1 text-zinc-500">Related work will appear here as it's created.</flux:text>
                    </div>
                @endforelse
            </div>
        </div>
    </flux:modal>
</div>
