@php($shown = $this->expanded ? $this->alerts : $this->alerts->take(3))
<div>
    @if ($this->alerts->isNotEmpty())
        <div class="border-b border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
            <div class="px-6 py-3 lg:px-8 space-y-2">
                @foreach ($shown as $a)
                    @php($tone = match ($a->severity) {
                        'critical' => ['bg' => 'bg-red-50 dark:bg-red-950/30', 'border' => 'border-red-300 dark:border-red-800', 'icon' => 'text-red-600 dark:text-red-400', 'badge' => 'red'],
                        'warning' => ['bg' => 'bg-amber-50 dark:bg-amber-950/30', 'border' => 'border-amber-300 dark:border-amber-800', 'icon' => 'text-amber-600 dark:text-amber-400', 'badge' => 'amber'],
                        default => ['bg' => 'bg-sky-50 dark:bg-sky-950/30', 'border' => 'border-sky-300 dark:border-sky-800', 'icon' => 'text-sky-600 dark:text-sky-400', 'badge' => 'sky'],
                    })

                    <div wire:key="alert-{{ $a->id }}"
                        class="flex flex-wrap items-center gap-3 rounded-lg border {{ $tone['border'] }} {{ $tone['bg'] }} px-4 py-2.5">
                        <flux:icon.exclamation-triangle class="size-5 shrink-0 {{ $tone['icon'] }}" />

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold">{{ $a->title }}</span>
                                @if ($a->severity === 'critical')
                                    <flux:badge size="sm" color="red">Critical</flux:badge>
                                @endif
                                @if ($a->role)
                                    <flux:badge size="sm" color="zinc">{{ $a->role }}</flux:badge>
                                @endif
                            </div>
                            @if ($a->body)
                                <div class="text-xs text-zinc-600 dark:text-zinc-400">{{ $a->body }}</div>
                            @endif
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            @if ($a->url)
                                <flux:button size="xs" variant="primary" :href="$a->url" wire:navigate>
                                    {{ $a->action_label ?: 'Open' }}
                                </flux:button>
                            @endif
                            {{-- Not a dismiss: this records who decided it was handled. --}}
                            <flux:button size="xs" variant="ghost" icon="check"
                                wire:click="resolve({{ $a->id }})"
                                wire:confirm="Mark this as handled? It will leave the banner for everyone.">
                                Handled
                            </flux:button>
                        </div>
                    </div>
                @endforeach

                @if ($this->alerts->count() > 3)
                    <button type="button" wire:click="$toggle('expanded')"
                        class="text-xs font-medium text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100">
                        {{ $this->expanded
                            ? 'Show fewer'
                            : '+ '.($this->alerts->count() - 3).' more needing action' }}
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>
