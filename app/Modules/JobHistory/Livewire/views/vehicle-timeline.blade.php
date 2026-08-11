@php($EVENT = \App\Modules\JobHistory\Models\JobCardHistoryEvent::class)
<div class="max-w-4xl">
    {{-- HEADER --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:link :href="route('customer-vehicle-master.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Customer Vehicles
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $vehicle->registration_no ?: 'Vehicle #'.$vehicle->id }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">
                Everything that has happened to this vehicle, oldest first.
                @if ($firstAt && $lastAt)
                    · {{ $firstAt->format('d M Y') }} → {{ $lastAt->format('d M Y') }}
                @endif
            </flux:text>
        </div>
        <flux:badge size="lg" color="zinc">{{ $rows->count() }} events</flux:badge>
    </div>

    {{-- VISIT FILTER --}}
    @if ($visits->isNotEmpty())
        <div class="mb-6 flex flex-wrap items-center gap-2">
            <flux:text size="sm" class="text-zinc-500">Visit:</flux:text>
            <flux:button size="xs" :variant="$jobCardFilter ? 'ghost' : 'primary'" wire:click="clearFilter">All</flux:button>
            @foreach ($visits as $v)
                <flux:button size="xs" :variant="$jobCardFilter === $v->job_card_id ? 'primary' : 'ghost'"
                    wire:click="$set('jobCardFilter', {{ $v->job_card_id }})">
                    {{ $v->jobCard->job_card_no }}
                </flux:button>
            @endforeach
        </div>
    @endif

    {{-- TIMELINE --}}
    @forelse ($rows as $row)
        @php($e = $row['event'])
        {{-- Gap marker: how long the vehicle sat between these two events. --}}
        @if ($row['gap_minutes'] !== null && $row['gap_minutes'] >= 60)
            <div class="flex items-center gap-3 py-1 pl-[7px]">
                <div class="w-px self-stretch bg-zinc-200 dark:bg-zinc-700 min-h-6"></div>
                <flux:text size="sm" class="{{ $row['gap_minutes'] >= 1440 ? 'text-amber-600 dark:text-amber-500' : 'text-zinc-400' }}">
                    @if ($row['gap_minutes'] >= 1440)
                        waited {{ intdiv($row['gap_minutes'], 1440) }}d {{ intdiv($row['gap_minutes'] % 1440, 60) }}h
                    @else
                        waited {{ intdiv($row['gap_minutes'], 60) }}h {{ $row['gap_minutes'] % 60 }}m
                    @endif
                </flux:text>
            </div>
        @endif

        <div class="flex gap-4 py-2" wire:key="ev-{{ $e->id }}">
            <div class="flex flex-col items-center">
                <div class="mt-1.5 size-3.5 shrink-0 rounded-full border-2 border-mq-orange-500 bg-white dark:bg-zinc-900"></div>
                @unless ($loop->last)
                    <div class="w-px flex-1 bg-zinc-200 dark:bg-zinc-700"></div>
                @endunless
            </div>

            <div class="min-w-0 flex-1 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <flux:heading size="sm">{{ $EVENT::types()[$e->event_type] ?? $e->event_type }}</flux:heading>
                    @if ($e->jobCard)
                        <flux:link :href="route('job-card.edit', $e->job_card_id)" wire:navigate class="font-mono text-xs">
                            {{ $e->jobCard->job_card_no }}
                        </flux:link>
                    @else
                        <flux:badge size="sm" color="sky">Pre job card</flux:badge>
                    @endif
                </div>
                <flux:text size="sm" class="mt-0.5">{{ $e->summary }}</flux:text>
                <flux:text size="sm" class="mt-0.5 text-zinc-400">
                    {{ $e->occurred_at?->format('d M Y, h:i A') }}
                    @if ($e->actor)· {{ $e->actor->name }}@endif
                </flux:text>
            </div>
        </div>
    @empty
        <div class="py-16 text-center">
            <flux:icon.clock class="mx-auto size-10 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-3">Nothing recorded yet</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Events appear here as this vehicle moves through the workshop.</flux:text>
        </div>
    @endforelse
</div>
