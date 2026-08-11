<div>
    <flux:dropdown align="end" class="w-full">
        {{-- Labelled, not just an icon: an unlabelled bell in a busy header is
             the thing nobody finds. --}}
        <flux:button
            :variant="$this->unreadCount > 0 ? 'filled' : 'ghost'"
            size="sm"
            icon="bell"
            icon:trailing="chevron-down">
            <span class="max-lg:hidden">Alerts</span>
            @if ($this->unreadCount > 0)
                <flux:badge size="sm" color="amber" class="ml-1">{{ $this->unreadCount }}</flux:badge>
            @endif
        </flux:button>

        <flux:menu class="w-80">
            {{-- Who is at this screen. Drives the bench and the notification list too. --}}
            <div class="px-3 py-2">
                <flux:select wire:model.live="employeeId" variant="listbox" searchable clearable size="sm"
                    label="I am" placeholder="Pick your name…">
                    @foreach ($this->employees as $e)
                        <flux:select.option :value="$e->id" wire:key="be-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:menu.separator />

            @if (! $employeeId)
                <div class="px-3 py-6 text-center">
                    <flux:text size="sm" class="text-zinc-500">Pick your name to see alerts meant for you.</flux:text>
                </div>
            @else
                @forelse ($this->recent as $n)
                    <flux:menu.item
                        wire:key="bn-{{ $n->id }}"
                        :href="$n->url ?: route('notification-center.index')"
                        wire:navigate>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium">{{ $n->title }}</div>
                            @if ($n->body)
                                <div class="truncate text-xs text-zinc-500">{{ $n->body }}</div>
                            @endif
                        </div>
                    </flux:menu.item>
                @empty
                    <div class="px-3 py-6 text-center">
                        <flux:icon.check-circle class="mx-auto size-6 text-zinc-300 dark:text-zinc-600" />
                        <flux:text size="sm" class="mt-1 text-zinc-500">Nothing unread.</flux:text>
                    </div>
                @endforelse

                <flux:menu.separator />
                <flux:menu.item icon="bell" :href="route('notification-center.index')" wire:navigate>
                    All notifications
                </flux:menu.item>
            @endif
        </flux:menu>
    </flux:dropdown>
</div>
