@php($SCOPE = \App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrderScope::class)
@php($TF = \App\Modules\TechnicianFinding\Models\TechnicianFinding::class)
@php($VIO = \App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder::class)
@php($vehicle = $order->jobCard?->customerVehicle)
<div class="max-w-5xl">
    {{-- HEADER --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl" level="1">Technician Response</flux:heading>
                <flux:badge size="sm" :color="match ($order->status) {
                    'assigned' => 'sky', 'wip' => 'blue', 'on_hold' => 'amber',
                    'completed' => 'lime', 'cancelled' => 'zinc', default => 'zinc',
                }">{{ $VIO::statuses()[$order->status] ?? $order->status }}</flux:badge>
            </div>
            <flux:text size="sm" class="mt-1 text-zinc-500 font-mono">{{ $order->order_no }}</flux:text>
        </div>
        <flux:button variant="ghost" icon="arrow-left" :href="route('technician-bench.index')" wire:navigate>Back to bench</flux:button>
    </div>

    {{-- VEHICLE — what a technician needs. The customer is the advisor's business. --}}
    <div class="mb-6 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
        <flux:heading size="sm" class="mb-3">Vehicle</flux:heading>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            @foreach ([
                'Reg. No.' => $vehicle ? \App\Support\RegistrationNumber::format($vehicle->registration_no) : null,
                'Vehicle' => $vehicle?->fullName(),
                'VIN' => $vehicle?->vin,
                'Engine No.' => $vehicle?->engine_no,
                'Colour' => $vehicle?->color?->name,
                'Year' => $vehicle?->year_of_manufacture,
                'Odometer' => $vehicle?->odometer_km ? number_format((float) $vehicle->odometer_km).' km' : null,
                'Job Card' => $order->jobCard?->job_card_no,
            ] as $label => $value)
                <div>
                    <div class="text-xs text-zinc-500">{{ $label }}</div>
                    <div class="mt-0.5 font-medium {{ in_array($label, ['Reg. No.', 'VIN', 'Engine No.', 'Job Card'], true) ? 'font-mono' : '' }}">
                        {{ $value ?: '—' }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- TAT — both ends kept, so a pause does not shrink the number. --}}
    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ([
            ['label' => 'Ordered', 'value' => $order->ordered_at?->format('d/m/Y h:i A')],
            ['label' => 'Work started', 'value' => $order->started_at?->format('d/m/Y h:i A')],
            ['label' => 'Work completed', 'value' => $order->ended_at?->format('d/m/Y h:i A')],
            ['label' => 'Time on the job', 'value' => gmdate('H:i:s', $this->totalSeconds)],
        ] as $stat)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
                <div class="text-xs text-zinc-500">{{ $stat['label'] }}</div>
                <div class="mt-1 font-medium tabular-nums">{{ $stat['value'] ?: '—' }}</div>
            </div>
        @endforeach
    </div>

    {{-- WHO --}}
    @if (! $technicianId)
        <flux:callout variant="warning" icon="user-circle" inline class="mb-6">
            <flux:callout.text>
                Pick your name on the <flux:link :href="route('technician-bench.index')" wire:navigate>bench</flux:link>
                before starting a timer — the clock has to be against someone.
            </flux:callout.text>
        </flux:callout>
    @endif

    {{-- TODO LIST --}}
    <flux:heading size="lg" class="mb-1">Todo list</flux:heading>
    <flux:text size="sm" class="mb-3 text-zinc-500">Customer complaints, job descriptions and requested repairs on this order.</flux:text>

    <div class="space-y-3 mb-8">
        @forelse ($this->todo as $task)
            <div wire:key="todo-{{ $task->id }}" class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-medium">
                            {{ $task->labour?->name
                                ?? $task->requestedRepair?->name
                                ?? $task->jobDescription?->name
                                ?? $task->servicePackage?->name
                                ?? \Illuminate\Support\Str::limit($task->description, 80) }}
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            @if ($task->complaintType)
                                <flux:badge size="sm" color="zinc">{{ $task->complaintType->name }}</flux:badge>
                            @endif
                            @if ($task->is_additional)
                                <flux:badge size="sm" color="amber">Additional</flux:badge>
                            @endif
                            <flux:badge size="sm" :color="match ($task->work_status) {
                                'in_progress' => 'blue', 'paused' => 'amber',
                                'completed' => 'lime', default => 'zinc',
                            }">{{ $SCOPE::workStatuses()[$task->work_status] ?? $task->work_status }}</flux:badge>
                            @if ($task->completion_type)
                                <flux:badge size="sm" color="lime" variant="pill">{{ $VIO::completionTypes()[$task->completion_type] ?? $task->completion_type }}</flux:badge>
                            @endif
                        </div>
                    </div>

                    <div class="text-end shrink-0">
                        <div class="font-mono text-sm tabular-nums">{{ gmdate('H:i:s', $task->elapsedSeconds()) }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">
                            {{ $task->work_started_at?->format('d/m/Y h:i A') ?? 'not started' }}
                            @if ($task->completed_at) → {{ $task->completed_at->format('h:i A') }} @endif
                        </div>
                    </div>
                </div>

                @can('technician_bench.update')
                    <div class="mt-3 flex flex-wrap gap-2">
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

                {{-- Before / after evidence for this line. --}}
                <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach (['before' => 'Before', 'after' => 'After'] as $stage => $label)
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <flux:text size="sm" class="font-medium">{{ $label }}</flux:text>
                                @can('technician_bench.update')
                                    <div class="flex items-center gap-1">
                                        <flux:input type="file" size="sm" multiple accept="image/*" capture="environment"
                                            wire:model="scopePhotoFiles.{{ $task->id }}.{{ $stage }}" class="max-w-40" />
                                        <flux:button size="xs" variant="ghost" icon="arrow-up-tray"
                                            wire:click="uploadScopePhotos({{ $task->id }}, '{{ $stage }}')">Save</flux:button>
                                    </div>
                                @endcan
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @forelse ($task->photos->where('stage', $stage) as $photo)
                                    <div class="relative" wire:key="rphoto-{{ $photo->id }}">
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
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-8 text-center text-sm text-zinc-500">
                Nothing on the todo list. The advisor sets the work scope on the order.
            </div>
        @endforelse
    </div>

    {{-- CHECKLISTS — named, so the technician knows which inspection they are on. --}}
    @if ($this->checklists->isNotEmpty())
        <flux:heading size="lg" class="mb-1">Vehicle inspection</flux:heading>
        <flux:text size="sm" class="mb-3 text-zinc-500">Checklists the advisor added to this order.</flux:text>

        <div class="space-y-3 mb-8">
            @foreach ($this->checklists as $templateName => $items)
                <div wire:key="cl-{{ \Illuminate\Support\Str::slug($templateName) }}" class="rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <div class="px-4 py-2.5 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between gap-2">
                        <flux:heading size="sm">{{ $templateName }}</flux:heading>
                        <flux:badge size="sm" color="zinc">{{ $items->count() }} checks</flux:badge>
                    </div>
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($items as $item)
                            <div class="px-4 py-2 flex items-center justify-between gap-3 text-sm" wire:key="cli-{{ $item->id }}">
                                <span class="truncate">{{ $item->label }}</span>
                                <flux:badge size="sm" :color="match ($item->result) {
                                    'ok' => 'lime', 'immediate' => 'red', 'future' => 'amber', default => 'zinc',
                                }">{{ $VIO::results()[$item->result] ?? $item->result }}</flux:badge>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- FINDINGS — the point of the page. --}}
    <div class="mb-3 flex items-end justify-between gap-3">
        <div>
            <flux:heading size="lg">Technician findings</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">
                Parts that need replacing and labour that has to be performed — a gearbox overhaul, a clutch kit.
                The advisor is notified and decides what goes on the bill.
            </flux:text>
        </div>
        @can('technician_bench.update')
            <div class="flex gap-2 shrink-0">
                <flux:button size="sm" variant="primary" icon="plus" wire:click="newFinding('spare')">Part</flux:button>
                <flux:button size="sm" variant="outline" icon="plus" wire:click="newFinding('labour')">Labour</flux:button>
            </div>
        @endcan
    </div>

    <div class="mb-8 rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-800">
        @forelse ($this->findings as $finding)
            <div wire:key="finding-{{ $finding->id }}" class="px-4 py-3 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm" :color="$finding->finding_type === $TF::TYPE_SPARE ? 'blue' : 'purple'">
                            {{ $finding->finding_type === $TF::TYPE_SPARE ? 'Part' : 'Labour' }}
                        </flux:badge>
                        <span class="font-medium text-sm truncate">{{ $finding->description }}</span>
                    </div>
                    <div class="text-xs text-zinc-500 mt-1">
                        {{ $finding->spare?->name ?? $finding->labour?->name ?? '—' }}
                        · Qty {{ rtrim(rtrim(number_format((float) $finding->quantity, 2), '0'), '.') }}
                        @if ($finding->estimated_amount) · ₹{{ number_format((float) $finding->estimated_amount, 2) }} @endif
                        @if ($finding->reportedBy) · {{ $finding->reportedBy->name }} @endif
                    </div>
                    @if ($finding->recommendation)
                        <div class="text-xs text-zinc-500 mt-1 italic">{{ $TF::recommendations()[$finding->recommendation] ?? $finding->recommendation }}</div>
                    @endif
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <flux:badge size="sm" :color="match ($finding->status) {
                        'approved' => 'lime', 'rejected' => 'red', 'converted' => 'sky', default => 'amber',
                    }">{{ ucfirst($finding->status) }}</flux:badge>
                    @can('technician_bench.update')
                        @if ($finding->status === $TF::STATUS_RECOMMENDED)
                            <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="editFinding({{ $finding->id }})" />
                            <flux:modal.trigger :name="'finding-delete-' . $finding->id">
                                <flux:button size="xs" variant="ghost" icon="trash" />
                            </flux:modal.trigger>
                            <flux:modal :name="'finding-delete-' . $finding->id" class="md:w-96">
                                <div class="space-y-4">
                                    <flux:heading size="lg">Remove this finding?</flux:heading>
                                    <flux:text>{{ $finding->description }}</flux:text>
                                    <div class="flex gap-2 justify-end">
                                        <flux:modal.close><flux:button variant="ghost">Keep</flux:button></flux:modal.close>
                                        <flux:button variant="danger" wire:click="deleteFinding({{ $finding->id }})"
                                            x-on:click="$flux.modal('finding-delete-{{ $finding->id }}').close()">Remove</flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        @endif
                    @endcan
                </div>
            </div>
        @empty
            <div class="px-4 py-8 text-center text-sm text-zinc-500">
                Nothing raised yet. Add a part or a labour job as you find it.
            </div>
        @endforelse
    </div>

    {{-- REMARK --}}
    <flux:heading size="lg" class="mb-1">Technician remark</flux:heading>
    <flux:text size="sm" class="mb-3 text-zinc-500">What you want the advisor to know about the job as a whole.</flux:text>

    <div class="mb-8 space-y-3">
        <flux:textarea wire:model="technicianRemark" rows="3" placeholder="Road tested, noise gone. Recommend tyre rotation next service." />
        <flux:error name="technicianRemark" />
        @can('technician_bench.update')
            <div class="flex justify-end">
                <flux:button variant="primary" icon="check" wire:click="saveRemark">Save remark</flux:button>
            </div>
        @endcan
    </div>

    {{-- MODALS --}}
    <flux:modal name="response-pause-reason" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Why are you pausing?</flux:heading>
                <flux:subheading>The gap is recorded with its reason and reopened when you resume.</flux:subheading>
            </div>
            <flux:select wire:model="pauseReasonId" variant="listbox" searchable label="Reason" placeholder="Waiting for parts, bay taken…">
                @foreach ($this->pauseReasons as $reason)
                    <flux:select.option :value="$reason->id" wire:key="rpr-{{ $reason->id }}">{{ $reason->name }}</flux:select.option>
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

    <flux:modal name="response-completion-type" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">How did this finish?</flux:heading>
                <flux:subheading>Recorded per line — one job can be done while another is reworked.</flux:subheading>
            </div>
            <flux:select wire:model="completionType" variant="listbox" label="Completion type" placeholder="Pick one…">
                @foreach ($VIO::completionTypes() as $key => $label)
                    <flux:select.option :value="$key" wire:key="rct-{{ $key }}">{{ $label }}</flux:select.option>
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

    <flux:modal name="response-finding" class="md:w-[32rem]">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingFindingId ? 'Edit finding' : 'New finding' }}</flux:heading>
                <flux:subheading>What has to be replaced or performed, and roughly what it costs.</flux:subheading>
            </div>

            <flux:radio.group wire:model.live="findingType" variant="segmented" label="Type">
                <flux:radio value="spare" label="Part" />
                <flux:radio value="labour" label="Labour" />
            </flux:radio.group>

            @if ($findingType === 'spare')
                <flux:select wire:model.live="findingSpareId" variant="combobox" clearable required
                    label="Part" placeholder="Search by name or code…">
                    <x-slot name="search">
                        <flux:select.search wire:model.live.debounce.250ms="spareSearch" placeholder="Search parts…" />
                    </x-slot>
                    @foreach ($this->spares as $spare)
                        <flux:select.option :value="$spare->id" wire:key="sp-{{ $spare->id }}">
                            {{ $spare->name }}{{ $spare->spare_code ? ' · '.$spare->spare_code : '' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="findingSpareId" />
            @else
                <flux:select wire:model.live="findingLabourId" variant="combobox" clearable required
                    label="Labour" placeholder="Gearbox overhaul, clutch replacement…">
                    <x-slot name="search">
                        <flux:select.search wire:model.live.debounce.250ms="labourSearch" placeholder="Search labour…" />
                    </x-slot>
                    @foreach ($this->labours as $labour)
                        <flux:select.option :value="$labour->id" wire:key="lb-{{ $labour->id }}">
                            {{ $labour->name }}{{ $labour->labour_code ? ' · '.$labour->labour_code : '' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="findingLabourId" />
            @endif

            <flux:input wire:model="findingDescription" label="Description" required
                placeholder="Replace clutch plate — slipping under load" />
            <flux:error name="findingDescription" />

            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="findingQuantity" type="number" step="0.01" min="0.01" label="Quantity" required />
                <flux:input wire:model="findingEstimatedAmount" type="number" step="0.01" min="0" label="Estimated ₹" placeholder="Optional" />
            </div>
            <flux:error name="findingQuantity" />

            <flux:select wire:model="findingRecommendation" variant="listbox" label="Recommendation">
                @foreach ($TF::recommendations() as $key => $label)
                    <flux:select.option :value="$key" wire:key="rec-{{ $key }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="findingRecommendation" />

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button variant="primary" wire:click="saveFinding">{{ $editingFindingId ? 'Save' : 'Add finding' }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
