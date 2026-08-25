<?php
?>
<div class="max-w-4xl">
    <div class="mb-8">
        <flux:link :href="route('job-card.edit', $jobCard)" variant="ghost" class="text-xs">
            <flux:icon.chevron-left class="inline size-3 -mt-0.5" />
            Back to Job Card
        </flux:link>
        <flux:heading size="xl" level="1" class="mt-1">
            History of {{ $jobCard->job_card_no }}
        </flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">
            @if ($jobCard->customer)
                {{ trim($jobCard->customer->first_name.' '.($jobCard->customer->last_name ?? '')) }}
            @endif
            @if ($jobCard->customerVehicle) · {{ $jobCard->customerVehicle->registration_no }} @endif
        </flux:text>
    </div>

    <flux:separator class="mb-6" />

    {{-- STAGE TREE --}}
    @if ($stages->isNotEmpty())
        <div class="mb-8">
            <flux:text size="sm" class="mb-3 font-medium text-zinc-600 dark:text-zinc-300">Status Tree</flux:text>
            <ol class="flex flex-wrap items-stretch gap-2">
                @foreach ($stages as $i => $stage)
                    @php
                        $isCurrent = $currentStageId === $stage->id;
                        $isDone = $currentStageId !== null && $stage->sort_order < $currentStageOrder;
                        $enteredAt = $stageEnteredAt[$stage->id] ?? null;
                    @endphp
                    <li wire:key="stage-{{ $stage->id }}" class="flex items-center gap-2">
                        <div class="rounded-lg border px-3 py-2 min-w-32
                            {{ $isCurrent ? 'border-mq-orange-500 bg-mq-orange-500/10' : ($isDone ? 'border-lime-500/40 bg-lime-500/5' : 'border-dashed border-zinc-300 dark:border-zinc-700') }}">
                            <div class="flex items-center gap-1.5">
                                @if ($isCurrent)
                                    <flux:icon.arrow-right-circle class="size-4 text-mq-orange-500" />
                                @elseif ($isDone)
                                    <flux:icon.check-circle class="size-4 text-lime-600" />
                                @else
                                    <flux:icon.clock class="size-4 text-zinc-400" />
                                @endif
                                <span class="text-xs font-medium {{ $isCurrent || $isDone ? '' : 'text-zinc-500' }}">{{ $stage->name }}</span>
                            </div>
                            <div class="mt-0.5 text-[11px] text-zinc-400">
                                {{ $enteredAt?->format('d/m, H:i') ?? '—' }}
                            </div>
                        </div>
                        @if (! $loop->last)
                            <flux:icon.chevron-right class="size-4 text-zinc-300 dark:text-zinc-600" />
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>

        <flux:separator class="mb-6" />
    @endif

    @if ($events->isEmpty())
        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-10 text-center text-sm text-zinc-500">
            No history events recorded yet.
        </div>
    @else
        <ol class="relative border-l border-zinc-200 dark:border-zinc-800 ml-4 space-y-6">
            @foreach ($events as $event)
                <li wire:key="event-{{ $event->id }}" class="ml-6">
                    <span class="absolute -left-2 flex size-4 items-center justify-center rounded-full bg-mq-orange-500"></span>
                    <div class="flex items-baseline gap-3 flex-wrap">
                        <flux:heading size="sm">{{ $types[$event->event_type] ?? ucfirst(str_replace('_', ' ', $event->event_type)) }}</flux:heading>
                        <flux:text size="xs" class="text-zinc-500">{{ $event->occurred_at?->format('d/m/Y, H:i') }}</flux:text>
                    </div>
                    <flux:text size="sm" class="mt-1">{{ $event->summary }}</flux:text>
                    @if ($event->actor)
                        <flux:text size="xs" class="mt-0.5 text-zinc-500">By {{ $event->actor->name ?? $event->actor->email }}</flux:text>
                    @endif
                </li>
            @endforeach
        </ol>
    @endif
</div>
