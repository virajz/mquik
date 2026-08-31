@use(App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder)
<div>
    <form wire:submit="save" novalidate class="max-w-7xl">
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <flux:link :href="route('vehicle-inspection-order.index')" variant="ghost" class="text-xs">
                    <flux:icon.chevron-left class="inline size-3 -mt-0.5" />
                    Vehicle Inspection Orders
                </flux:link>
                <flux:heading size="xl" level="1" class="mt-1">
                    {{ $editingId ? 'Work Order '.$order_no : 'New Work Order' }}
                </flux:heading>
            </div>
            @if ($editingId)
                <flux:badge :color="match ($status) {
                    'assignment_pending' => 'amber', 'assigned' => 'sky', 'wip' => 'blue',
                    'on_hold' => 'orange', 'completed' => 'lime', 'cancelled' => 'zinc', default => 'zinc',
                }" size="lg">{{ VehicleInspectionOrder::statuses()[$status] }}</flux:badge>
            @endif
        </div>

        <flux:separator class="mb-6" />

        @if (! $editingId)
            {{-- LEAN CREATE --}}
            @include('vehicle-inspection-order::partials.section-details', ['lean' => true])

            <flux:callout class="mt-6" icon="information-circle" variant="secondary">
                <flux:callout.text>Create the work order first — the checklist, photos and time tracking unlock once it exists.</flux:callout.text>
            </flux:callout>

            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('vehicle-inspection-order.index')" variant="ghost" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Create Work Order</flux:button>
            </div>
        @else
            {{-- RICH EDIT --}}
            <flux:tab.group>
                <flux:tabs wire:model="activeTab">
                    <flux:tab name="details" icon="clipboard-document-list">Details</flux:tab>
                    <flux:tab name="scope" icon="rectangle-stack">Work Scope <flux:badge size="sm" class="ml-1">{{ count($workScopes) }}</flux:badge></flux:tab>
                    <flux:tab name="checklist" icon="list-bullet">Checklist &amp; Photos <flux:badge size="sm" class="ml-1">{{ count($items) }}</flux:badge></flux:tab>
                    <flux:tab name="evidence" icon="camera">Evidence <flux:badge size="sm" class="ml-1">{{ count($photos) }}</flux:badge></flux:tab>
                    <flux:tab name="time" icon="clock">Time &amp; Status</flux:tab>
                </flux:tabs>

                <flux:tab.panel name="details" class="pt-6">
                    @include('vehicle-inspection-order::partials.section-details', ['lean' => false])
                </flux:tab.panel>

                {{-- WORK SCOPE — what this order is inspecting --}}
                <flux:tab.panel name="scope" class="pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="lg">Work Scope</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">
                                The technician's to-do list for this order. Pulled from the job card's complaints and requested repairs — classify a line only if you need to.
                            </flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:button type="button" size="sm" variant="outline" icon="arrow-down-tray" wire:click="fetchScopeFromJobCard">Pull from job card</flux:button>
                            <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addWorkScope">Add scope</flux:button>
                        </div>
                    </div>

                    @forelse ($workScopes as $i => $scope)
                        <div wire:key="scope-{{ $i }}" class="space-y-3 p-3 mb-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                            {{-- The task itself, in the technician's words, comes
                                 first. Everything that classifies it for billing
                                 is folded away until an advisor needs it. --}}
                            <div class="flex items-start gap-2">
                                <div class="flex-1 min-w-0">
                                    <flux:input wire:model="workScopes.{{ $i }}.description" size="sm"
                                        placeholder="e.g. PMS, FR SIDE NOISE, REAR SIDE NOISE" required />
                                    <flux:error name="workScopes.{{ $i }}.description" />
                                </div>
                                <flux:button type="button" variant="ghost" icon="trash" wire:click="removeWorkScope({{ $i }})" />
                            </div>

                            <details class="group">
                                <summary class="cursor-pointer text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 select-none">
                                    Classify this line — complaint, job description, package, labour, technician
                                </summary>
                                <div class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-2">
                                    <flux:select wire:model="workScopes.{{ $i }}.complaint_type_id" variant="listbox" size="sm" searchable clearable label="Complaint" placeholder="Optional…">
                                        @foreach ($this->complaintTypes as $t)
                                            <flux:select.option :value="$t->id" wire:key="sct-{{ $i }}-{{ $t->id }}">{{ $t->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:select wire:model="workScopes.{{ $i }}.job_description_id" variant="listbox" size="sm" searchable clearable label="Job Description" placeholder="Optional…">
                                        @foreach ($this->jobDescriptions as $j)
                                            <flux:select.option :value="$j->id" wire:key="sjd-{{ $i }}-{{ $j->id }}">{{ $j->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    {{-- Labour, not package: a technician acts on
                                         "wheel alignment", and the labour's own
                                         department is what routes it to the right one. --}}
                                    <flux:select wire:model="workScopes.{{ $i }}.labour_id" variant="listbox" size="sm" searchable clearable label="Labour" placeholder="PMS, Wheel Alignment…">
                                        @foreach ($this->labours as $l)
                                            <flux:select.option :value="$l->id" wire:key="slab-{{ $i }}-{{ $l->id }}">
                                                {{ $l->name }}@if ($l->workshopDepartment) · {{ $l->workshopDepartment->name }} @endif
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:select wire:model="workScopes.{{ $i }}.requested_repair_id" variant="listbox" size="sm" searchable clearable label="Requested Repair" placeholder="Optional…">
                                        @foreach ($this->requestedRepairs as $r)
                                            <flux:select.option :value="$r->id" wire:key="srr-{{ $i }}-{{ $r->id }}">{{ $r->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>
                            </details>

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                {{-- Raised by the technician mid-job. Chargeable work
                                     is not theirs to bill, so the advisor confirms it
                                     before it can reach an invoice. --}}
                                <div class="flex flex-wrap items-center gap-2">
                                    <flux:checkbox wire:model.live="workScopes.{{ $i }}.is_additional" label="Additional check" />

                                    @if (! empty($scope['is_additional']))
                                        <flux:checkbox wire:model.live="workScopes.{{ $i }}.is_chargeable" label="Chargeable" />

                                        @if (! empty($scope['is_chargeable']))
                                            @if (! empty($scope['approved_at']))
                                                <flux:badge color="lime" size="sm">Advisor approved</flux:badge>
                                                <flux:button type="button" size="xs" variant="ghost" wire:click="revokeAdditionalWork({{ $i }})">Revoke</flux:button>
                                            @else
                                                <flux:badge color="amber" size="sm">Awaiting advisor</flux:badge>
                                                @can('vehicle_inspection_order.update')
                                                    <flux:button type="button" size="xs" variant="primary" icon="check"
                                                        wire:click="approveAdditionalWork({{ $i }})">Approve</flux:button>
                                                @endcan
                                            @endif
                                        @endif
                                    @endif
                                </div>

                                {{-- Per-task timer --}}
                                @if (! empty($scope['id']))
                                    @php($ws = $scope['work_status'] ?? 'pending')
                                    <div wire:key="timer-{{ $i }}-{{ $ws }}-{{ $scope['run_started_at'] ?? 0 }}-{{ $scope['duration_seconds'] ?? 0 }}"
                                        class="flex items-center gap-2"
                                        x-data="{
                                            base: {{ (int) ($scope['duration_seconds'] ?? 0) }},
                                            start: {{ $scope['run_started_at'] ? (int) $scope['run_started_at'] : 'null' }},
                                            now: Math.floor(Date.now() / 1000),
                                            t: null,
                                            get elapsed() { return this.base + (this.start ? (this.now - this.start) : 0) },
                                            fmt(s) { return new Date(Math.max(0, s) * 1000).toISOString().substr(11, 8) },
                                            init() { if (this.start) { this.t = setInterval(() => this.now = Math.floor(Date.now() / 1000), 1000) } },
                                            destroy() { if (this.t) clearInterval(this.t) }
                                        }">
                                        @php($sc = match ($ws) { 'in_progress' => 'sky', 'paused' => 'amber', 'completed' => 'lime', default => 'zinc' })
                                        <flux:badge :color="$sc" size="sm">{{ \App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrderScope::workStatuses()[$ws] ?? $ws }}</flux:badge>
                                        <span class="font-mono tabular-nums text-sm text-zinc-600 dark:text-zinc-300" x-text="fmt(elapsed)"></span>
                                        @if ($ws === 'in_progress')
                                            <flux:button type="button" size="xs" variant="ghost" icon="pause" wire:click="pauseScope({{ $i }})">Pause</flux:button>
                                            <flux:button type="button" size="xs" variant="primary" icon="check" wire:click="completeScope({{ $i }})">Complete</flux:button>
                                        @elseif ($ws === 'completed')
                                            <flux:button type="button" size="xs" variant="ghost" icon="play" wire:click="startScope({{ $i }})">Resume</flux:button>
                                        @else
                                            <flux:button type="button" size="xs" variant="primary" icon="play" wire:click="startScope({{ $i }})">Start</flux:button>
                                        @endif
                                    </div>
                                @else
                                    <flux:text size="sm" class="text-zinc-500">Save the order to enable the work timer.</flux:text>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                            No work scope yet. <span class="font-medium">Pull from job card</span> brings in its complaints and requested repairs.
                        </div>
                    @endforelse
                </flux:tab.panel>

                {{-- EVIDENCE — order-level photos + technician findings --}}
                <flux:tab.panel name="evidence" class="pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="lg">Photo Evidence</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">
                                Whole-vehicle views and fault evidence, separate from the per-item before/after shots.
                            </flux:text>
                        </div>
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addPhoto">Add photo</flux:button>
                    </div>

                    @forelse ($photos as $i => $photo)
                        <div wire:key="vphoto-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_1fr_auto] gap-2 items-end mb-3">
                            <flux:select wire:model="photos.{{ $i }}.photo_type_id" variant="listbox" size="sm" searchable clearable label="View" placeholder="Front, Damage…">
                                @foreach ($this->photoTypes as $pt)
                                    <flux:select.option :value="$pt->id" wire:key="vpt-{{ $i }}-{{ $pt->id }}">{{ $pt->name }}</flux:select.option>
                                @endforeach
                            </flux:select>

                            <div>
                                <flux:input type="file" size="sm" wire:model="photoFiles.{{ $i }}" label="Image" accept="image/*" />
                                @if ($photo['path'])
                                    <flux:text size="xs" class="text-zinc-500 mt-1">Saved: {{ basename($photo['path']) }}</flux:text>
                                @endif
                                <flux:error name="photoFiles.{{ $i }}" />
                            </div>

                            <flux:input wire:model="photos.{{ $i }}.notes" size="sm" label="Note" placeholder="Optional" />
                            <flux:button type="button" variant="ghost" icon="trash" wire:click="removePhoto({{ $i }})" />
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                            No photo evidence yet.
                        </div>
                    @endforelse

                    <flux:separator class="my-6" />

                    <div class="mb-3">
                        <flux:heading size="lg">Additional Work / Technician Findings</flux:heading>
                        <flux:text size="sm" class="mt-1 text-zinc-500">
                            Raised on the Technician Bench while the work happens. Nothing is entered here — the advisor's job is to
                            confirm anything major that will be charged on the bill.
                        </flux:text>
                    </div>

                    @forelse ($this->findings as $finding)
                        <div wire:key="find-{{ $finding->id }}" class="flex items-start justify-between gap-3 py-2 border-b border-zinc-100 dark:border-zinc-800">
                            <div>
                                <div class="font-medium text-sm">{{ $finding->description }}</div>
                                <div class="text-xs text-zinc-500 mt-0.5">
                                    {{ $finding->spare?->name ?? $finding->labour?->name ?? '—' }}
                                    · Qty {{ $finding->quantity }}
                                    @if ($finding->estimated_amount) · ₹{{ number_format((float) $finding->estimated_amount, 2) }} @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <flux:badge size="sm" :color="match ($finding->status) {
                                    'approved' => 'lime', 'rejected' => 'red', default => 'amber',
                                }">{{ ucfirst($finding->status) }}</flux:badge>
                                @if ($finding->status === 'approved')
                                    <flux:button type="button" size="xs" variant="ghost" wire:click="revokeAdditionalWork({{ $finding->id }})">Revoke</flux:button>
                                @else
                                    <flux:button type="button" size="xs" variant="primary" icon="check" wire:click="approveAdditionalWork({{ $finding->id }})">Approve</flux:button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <flux:text size="sm" class="text-zinc-500">Nothing raised on this order yet.</flux:text>
                    @endforelse
                </flux:tab.panel>

                <flux:tab.panel name="checklist" class="pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="lg">Checklist</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">
                                The advisor adds the templates; the technician works through them and records what they found.
                                Before/after photos belong to the work scope, on the bench.
                            </flux:text>
                        </div>

                        <div class="flex flex-wrap items-end gap-2 mb-4">
                            {{-- The technician picks the checklist that matches what
                                 they are doing; several can sit on one order. --}}
                            <div class="w-64">
                                <flux:select wire:model="addTemplateId" variant="listbox" searchable size="sm"
                                    label="Add a checklist" placeholder="PMS, Tyre, Bodyshop…">
                                    @foreach ($this->templates as $t)
                                        <flux:select.option :value="(string) $t->id" wire:key="addtpl-{{ $t->id }}">{{ $t->name }} ({{ strtoupper($t->applies_to) }})</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            <flux:button type="button" size="sm" variant="outline" icon="plus" wire:click="addTemplateItems">Add</flux:button>

                            @php($onOrder = collect($items)->pluck('inspection_template_id')->filter()->unique())
                            @if ($onOrder->isNotEmpty())
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @foreach ($this->templates->whereIn('id', $onOrder) as $t)
                                        <flux:badge size="sm" wire:key="ontpl-{{ $t->id }}">
                                            {{ $t->name }}
                                            <flux:badge.close wire:click="removeTemplateItems({{ $t->id }})" />
                                        </flux:badge>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    @if (count($items) === 0)
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                            No checklist yet. Use <span class="font-medium">Add a checklist</span> above — the technician works through whatever is added here.
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($items as $i => $item)
                                <div wire:key="item-{{ $i }}" class="space-y-3 p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                                    <div class="grid grid-cols-1 md:grid-cols-[1fr_160px] gap-2 items-end">
                                        {{-- The checkpoint's wording belongs to the
                                             template the advisor added; the technician
                                             records what they found, not what to check. --}}
                                        <flux:field>
                                            <flux:label>Item</flux:label>
                                            <div class="flex items-center h-9 text-sm font-medium">{{ $item['label'] }}</div>
                                        </flux:field>
                                        <flux:select wire:model="items.{{ $i }}.result" variant="listbox" size="sm" label="Result">
                                            @foreach (VehicleInspectionOrder::results() as $key => $label)
                                                <flux:select.option :value="$key" wire:key="res-{{ $i }}-{{ $key }}">{{ $label }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>

                                    @if ($item['group_name'])
                                        <flux:text size="xs" class="text-zinc-500">{{ $item['group_name'] }}</flux:text>
                                    @endif

                                    <flux:input wire:model="items.{{ $i }}.notes" size="sm" placeholder="Technician note / observation" />

                                                                        </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </flux:tab.panel>

                <flux:tab.panel name="time" class="pt-6">
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
                        <div>
                            <flux:heading size="lg">Status &amp; Time Tracking</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">
                                Read-only. The status follows the technician's timers, and every pause carries the reason they gave.
                            </flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <flux:field>
                                <flux:label>Status</flux:label>
                                <div class="flex items-center gap-2 h-9">
                                    <flux:badge size="sm" :color="match ($status) {
                                        'assigned' => 'blue',
                                        'wip' => 'sky',
                                        'on_hold' => 'amber',
                                        'completed' => 'lime',
                                        'cancelled' => 'zinc',
                                        default => 'zinc',
                                    }">{{ VehicleInspectionOrder::statuses()[$status] ?? $status }}</flux:badge>
                                    <flux:text size="sm" class="text-zinc-500">{{ $this->statusExplanation }}</flux:text>
                                </div>
                            </flux:field>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <flux:select wire:model="delay_reason_id" variant="listbox" searchable clearable label="Delay Reason" placeholder="If delayed…">
                                    @foreach ($this->delayReasons as $r)
                                        <flux:select.option :value="$r->id" wire:key="dr-{{ $r->id }}">{{ $r->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="rework_reason_id" variant="listbox" searchable clearable label="Rework Reason" placeholder="If rework…">
                                    @foreach ($this->reworkReasons as $r)
                                        <flux:select.option :value="$r->id" wire:key="rr-{{ $r->id }}">{{ $r->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <flux:separator variant="subtle" />

                            {{-- Completion type is recorded per line by whoever did the
                                 work, so one job can be done while another is reworked. --}}
                            <flux:heading size="sm">Completion by work scope line</flux:heading>
                            @if (count($workScopes) === 0)
                                <flux:text size="sm" class="text-zinc-500">No work scope on this order yet.</flux:text>
                            @else
                                <div class="rounded-md border border-zinc-200 dark:border-zinc-800 divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @foreach ($workScopes as $i => $scope)
                                        <div wire:key="ct-sum-{{ $i }}" class="px-3 py-2 flex items-center justify-between gap-3 text-sm">
                                            <span class="truncate">{{ $scope['description'] ?: '—' }}</span>
                                            <span class="shrink-0 text-zinc-500">
                                                {{ ($scope['completion_type'] ?? null)
                                                    ? (VehicleInspectionOrder::completionTypes()[$scope['completion_type']] ?? $scope['completion_type'])
                                                    : 'Not finished' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <flux:separator variant="subtle" />

                            <flux:heading size="sm">Pause / Resume Log</flux:heading>
                            @if (count($pauses) === 0)
                                <flux:text size="sm" class="text-zinc-500">No pauses logged. The technician's Pause button writes these.</flux:text>
                            @else
                                <div class="rounded-md border border-zinc-200 dark:border-zinc-800 divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @foreach ($pauses as $i => $pause)
                                        <div wire:key="pause-{{ $i }}" class="px-3 py-2 grid grid-cols-1 md:grid-cols-3 gap-2 text-sm">
                                            <div>
                                                <span class="text-zinc-500">Paused</span>
                                                {{ $pause['paused_date'] ? \Illuminate\Support\Carbon::parse($pause['paused_date'])->format('d/m/Y') : '—' }}
                                                {{ $pause['paused_time'] }}
                                            </div>
                                            <div>
                                                <span class="text-zinc-500">Resumed</span>
                                                {{ $pause['resumed_date'] ? \Illuminate\Support\Carbon::parse($pause['resumed_date'])->format('d/m/Y') : 'still paused' }}
                                                {{ $pause['resumed_time'] }}
                                            </div>
                                            <div class="text-zinc-500">
                                                {{ $this->holdReasons->firstWhere('id', $pause['hold_reason_id'])?->name ?? '—' }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <flux:textarea wire:model="notes" label="Notes" placeholder="Overall notes for this work order." rows="2" />
                        </div>
                    </section>
                </flux:tab.panel>
            </flux:tab.group>
        @endif

        @if ($editingId)
            <flux:separator class="mt-4" />
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('vehicle-inspection-order.index')" variant="ghost" wire:navigate>Close</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Save Changes</flux:button>
            </div>
        @endif
    </form>
</div>
