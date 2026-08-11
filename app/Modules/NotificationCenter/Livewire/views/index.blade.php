@php($N = \App\Modules\NotificationCenter\Models\AppNotification::class)
<div class="max-w-4xl">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Notifications</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Alerts raised for you as work moves through the workshop.</flux:text>
        </div>
        @if ($employeeId && $this->unreadCount > 0)
            <div class="flex shrink-0 items-center gap-2">
                <flux:badge color="amber" size="lg">{{ $this->unreadCount }} unread</flux:badge>
                <flux:button size="sm" variant="ghost" icon="check" wire:click="markAllRead">Mark all read</flux:button>
            </div>
        @endif
    </div>

    <div class="mb-6 flex flex-wrap items-end gap-3">
        <flux:select wire:model.live="employeeId" variant="listbox" searchable clearable
            label="I am" placeholder="Pick your name…" class="w-full sm:w-72">
            @foreach ($this->employees as $e)
                <flux:select.option :value="$e->id" wire:key="emp-{{ $e->id }}">{{ $e->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="typeFilter" variant="listbox" label="Type" class="w-full sm:w-56">
            <flux:select.option value="all">All types</flux:select.option>
            @foreach ($N::types() as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:switch wire:model.live="unreadOnly" label="Unread only" />
    </div>

    @if (! $employeeId)
        <div class="py-16 text-center">
            <flux:icon.bell class="mx-auto size-10 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-3">Pick your name to see your alerts</flux:heading>
        </div>
    @else
        <div class="space-y-2">
            @forelse ($rows as $n)
                <div wire:key="n-{{ $n->id }}"
                    class="flex items-start gap-3 rounded-lg border p-3 {{ $n->isUnread() ? 'border-mq-orange-500/40 bg-mq-orange-500/5' : 'border-zinc-200 dark:border-zinc-800' }}">
                    <flux:icon.bell class="mt-0.5 size-5 shrink-0 {{ $n->isUnread() ? 'text-mq-orange-500' : 'text-zinc-400' }}" />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="sm">{{ $n->title }}</flux:heading>
                            <flux:badge size="sm" color="zinc">{{ $N::types()[$n->type] ?? $n->type }}</flux:badge>
                        </div>
                        @if ($n->body)
                            <flux:text size="sm" class="mt-0.5">{{ $n->body }}</flux:text>
                        @endif
                        <flux:text size="sm" class="mt-0.5 text-zinc-400">
                            {{ $n->created_at?->diffForHumans() }}
                            @if ($n->actor)· {{ $n->actor->name }}@endif
                        </flux:text>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        @if ($n->url)
                            <flux:button size="xs" variant="ghost" icon="arrow-top-right-on-square" :href="$n->url" wire:navigate>Open</flux:button>
                        @endif
                        @if ($n->isUnread())
                            <flux:button size="xs" variant="ghost" icon="check" wire:click="markRead({{ $n->id }})">Read</flux:button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-16 text-center">
                    <flux:icon.check-circle class="mx-auto size-10 text-zinc-300 dark:text-zinc-600" />
                    <flux:heading size="lg" class="mt-3">{{ $unreadOnly ? 'Nothing unread' : 'No notifications' }}</flux:heading>
                    <flux:text size="sm" class="mt-1 text-zinc-500">
                        {{ $unreadOnly ? 'You are all caught up.' : 'Alerts will appear here as work progresses.' }}
                    </flux:text>
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            <flux:pagination :paginator="$rows" />
        </div>
    @endif
</div>
