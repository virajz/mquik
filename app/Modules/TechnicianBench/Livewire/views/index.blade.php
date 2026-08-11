@php($SCOPE = \App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrderScope::class)
<div class="max-w-5xl">
    {{-- HEADER --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Technician Bench</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Pick who you are, then start and stop your task timers.</flux:text>
        </div>
        @if ($this->runningTask)
            <flux:badge color="blue" size="lg" icon="play">
                Running · {{ $this->runningTask->order?->order_no }}
            </flux:badge>
        @endif
    </div>

    {{-- WHO + FILTER --}}
    <div class="mb-6 flex flex-wrap items-end gap-3">
        <flux:select wire:model.live="technicianId" variant="listbox" searchable clearable
            label="I am" placeholder="Pick your name…" class="w-full sm:w-72">
            @foreach ($this->technicians as $t)
                <flux:select.option :value="$t->id" wire:key="tech-{{ $t->id }}">{{ $t->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:switch wire:model.live="openOnly" label="Hide completed" />
    </div>

    @if (! $technicianId)
        <div class="py-16 text-center">
            <flux:icon.user-circle class="mx-auto size-10 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-3">Pick your name to begin</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Your assigned tasks will appear here.</flux:text>
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Task</flux:table.column>
                <flux:table.column>Work Order</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Time</flux:table.column>
                <flux:table.column class="w-64" align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->tasks as $task)
                    <flux:table.row wire:key="task-{{ $task->id }}">
                        <flux:table.cell>
                            <div class="font-medium">
                                {{ $task->labour?->name
                                    ?? $task->requestedRepair?->name
                                    ?? $task->jobDescription?->name
                                    ?? $task->servicePackage?->name
                                    ?? \Illuminate\Support\Str::limit($task->description, 60) }}
                            </div>
                            @if ($task->is_additional)
                                <flux:badge color="amber" size="sm" class="mt-1">Additional</flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="font-mono text-xs text-zinc-500">{{ $task->order?->order_no }}</div>
                            @if ($task->order?->jobCard?->job_card_no)
                                <div class="text-xs text-zinc-400">{{ $task->order->jobCard->job_card_no }}</div>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge size="sm" :color="match ($task->work_status) {
                                'in_progress' => 'blue', 'paused' => 'amber',
                                'completed' => 'lime', default => 'zinc',
                            }">{{ $this->workStatuses[$task->work_status] ?? $task->work_status }}</flux:badge>
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            <span class="font-mono text-sm">{{ gmdate('H:i:s', $task->elapsedSeconds()) }}</span>
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            @can('technician_bench.update')
                                <div class="flex justify-end gap-2">
                                    @if ($task->work_status === $SCOPE::STATUS_COMPLETED)
                                        <flux:text size="sm" class="text-zinc-400">Done</flux:text>
                                    @elseif ($task->isRunning())
                                        <flux:button size="xs" variant="ghost" icon="pause" wire:click="pause({{ $task->id }})">Pause</flux:button>
                                        <flux:button size="xs" variant="primary" icon="check" wire:click="complete({{ $task->id }})">Complete</flux:button>
                                    @else
                                        <flux:button size="xs" variant="primary" icon="play" wire:click="start({{ $task->id }})">
                                            {{ $task->duration_seconds > 0 ? 'Resume' : 'Start' }}
                                        </flux:button>
                                        <flux:button size="xs" variant="ghost" icon="check" wire:click="complete({{ $task->id }})">Complete</flux:button>
                                    @endif
                                </div>
                            @endcan
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <div class="py-12 text-center">
                                <flux:icon.wrench-screwdriver class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                                <flux:heading size="lg" class="mt-3">Nothing assigned</flux:heading>
                                <flux:text size="sm" class="mt-1 text-zinc-500">
                                    {{ $openOnly ? 'No open tasks — try showing completed ones.' : 'No work orders assign tasks to you yet.' }}
                                </flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    @endif
</div>
