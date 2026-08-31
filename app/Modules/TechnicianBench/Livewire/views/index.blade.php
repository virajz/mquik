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
                            {{-- The full response page for this order: vehicle,
                                 todo list, findings and the closing remark. --}}
                            <flux:link :href="route('technician-bench.response', $task->order)" wire:navigate class="font-mono text-xs">
                                {{ $task->order?->order_no }}
                            </flux:link>
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
                                        <flux:button size="xs" variant="ghost" icon="pause" wire:click="askPauseReason({{ $task->id }})">Pause</flux:button>
                                        <flux:button size="xs" variant="primary" icon="check" wire:click="askCompletionType({{ $task->id }})">Complete</flux:button>
                                    @else
                                        <flux:button size="xs" variant="primary" icon="play" wire:click="start({{ $task->id }})">
                                            {{ $task->duration_seconds > 0 ? 'Resume' : 'Start' }}
                                        </flux:button>
                                        <flux:button size="xs" variant="ghost" icon="check" wire:click="askCompletionType({{ $task->id }})">Complete</flux:button>
                                    @endif
                                </div>
                            @endcan
                        </flux:table.cell>
                    </flux:table.row>

                    {{-- Evidence belongs to the work, not to a checklist tick:
                         several before and after shots per line. --}}
                    <flux:table.row wire:key="evidence-{{ $task->id }}">
                        <flux:table.cell colspan="5" class="bg-zinc-50/60 dark:bg-zinc-800/30">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach (['before' => 'Before', 'after' => 'After'] as $stage => $label)
                                    <div>
                                        <div class="flex items-center justify-between gap-2 mb-1">
                                            <flux:text size="sm" class="font-medium">{{ $label }}</flux:text>
                                            @can('technician_bench.update')
                                                <div class="flex items-center gap-1">
                                                    <flux:input type="file" size="sm" multiple accept="image/*" capture="environment"
                                                        wire:model="scopePhotoFiles.{{ $task->id }}.{{ $stage }}" class="max-w-44" />
                                                    <flux:button size="xs" variant="ghost" icon="arrow-up-tray"
                                                        wire:click="uploadScopePhotos({{ $task->id }}, '{{ $stage }}')">Save</flux:button>
                                                </div>
                                            @endcan
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            @forelse ($task->photos->where('stage', $stage) as $photo)
                                                <div class="relative" wire:key="photo-{{ $photo->id }}">
                                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo->path) }}"
                                                        alt="{{ $label }}" class="h-16 w-16 object-cover rounded border border-zinc-200 dark:border-zinc-700" />
                                                    @can('technician_bench.update')
                                                        <button type="button" wire:click="removeScopePhoto({{ $photo->id }})"
                                                            class="absolute -top-1.5 -end-1.5 rounded-full bg-zinc-900/80 text-white size-4 text-[10px] leading-none">×</button>
                                                    @endcan
                                                </div>
                                            @empty
                                                <flux:text size="xs" class="text-zinc-400">No {{ strtolower($label) }} photos yet.</flux:text>
                                            @endforelse
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if ($task->completion_type)
                                <flux:text size="xs" class="text-zinc-500 mt-2">
                                    Completed as {{ \App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder::completionTypes()[$task->completion_type] ?? $task->completion_type }}
                                </flux:text>
                            @endif
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

    <flux:modal name="technician-pause-reason" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Why are you pausing?</flux:heading>
                <flux:subheading>The gap is recorded with its reason and reopened when you resume.</flux:subheading>
            </div>
            <flux:select wire:model="pauseReasonId" variant="listbox" searchable label="Reason" placeholder="Waiting for parts, bay taken…">
                @foreach ($this->pauseReasons as $reason)
                    <flux:select.option :value="$reason->id" wire:key="pr-{{ $reason->id }}">{{ $reason->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="pauseReasonId" />
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close><flux:button variant="ghost">Keep working</flux:button></flux:modal.close>
                <flux:button variant="primary" wire:click="pause">Pause</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="technician-completion-type" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">How did this finish?</flux:heading>
                <flux:subheading>Recorded per line — one job can be done while another is reworked.</flux:subheading>
            </div>
            <flux:select wire:model="completionType" variant="listbox" label="Completion type" placeholder="Pick one…">
                @foreach (\App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder::completionTypes() as $key => $label)
                    <flux:select.option :value="$key" wire:key="ct-{{ $key }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="completionType" />
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button variant="primary" wire:click="complete">Complete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>