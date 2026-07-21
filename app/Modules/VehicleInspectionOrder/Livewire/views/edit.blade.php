@use(App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder)
<div>
    <form wire:submit="save" class="max-w-7xl">
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
                                What this order covers — a complaint, a job description, or a service / combo / AMC package.
                            </flux:text>
                        </div>
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addWorkScope">Add scope</flux:button>
                    </div>

                    @forelse ($workScopes as $i => $scope)
                        <div wire:key="scope-{{ $i }}" class="space-y-3 p-3 mb-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                            <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr_1fr_auto] gap-2 items-end">
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

                                <flux:select wire:model="workScopes.{{ $i }}.service_package_id" variant="listbox" size="sm" searchable clearable label="Package" placeholder="Service / Combo / AMC…">
                                    @foreach ($this->servicePackages as $pkg)
                                        <flux:select.option :value="$pkg->id" wire:key="spk-{{ $i }}-{{ $pkg->id }}">
                                            {{ $pkg->name }}{{ $pkg->packageType ? ' · '.$pkg->packageType->name : '' }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:button type="button" variant="ghost" icon="trash" wire:click="removeWorkScope({{ $i }})" />
                            </div>

                            <flux:input wire:model="workScopes.{{ $i }}.description" size="sm" placeholder="e.g. PMS, FR SIDE NOISE, REAR SIDE NOISE" required />
                            <flux:error name="workScopes.{{ $i }}.description" />
                        </div>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                            No work scope recorded yet.
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
                            Extra spares or labour raised against this order. Managed in Technician Findings.
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
                            <flux:badge size="sm" :color="match ($finding->status) {
                                'approved' => 'lime', 'rejected' => 'red', default => 'amber',
                            }">{{ ucfirst($finding->status) }}</flux:badge>
                        </div>
                    @empty
                        <flux:text size="sm" class="text-zinc-500">No additional work raised on this order.</flux:text>
                    @endforelse
                </flux:tab.panel>

                <flux:tab.panel name="checklist" class="pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="lg">Checklist &amp; Photo Evidence</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Mark each item OK / Immediate Action / Future Action, with before &amp; after photos.</flux:text>
                        </div>
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add item</flux:button>
                    </div>

                    @if (count($items) === 0)
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                            No checklist items. Pick a template on the Details tab, or add items manually.
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($items as $i => $item)
                                <div wire:key="item-{{ $i }}" class="space-y-3 p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                                    <div class="grid grid-cols-1 md:grid-cols-[1fr_160px_auto] gap-2 items-end">
                                        <flux:input wire:model="items.{{ $i }}.label" size="sm" label="Item" placeholder="Checkpoint" />
                                        <flux:select wire:model="items.{{ $i }}.result" variant="listbox" size="sm" label="Result">
                                            @foreach (VehicleInspectionOrder::results() as $key => $label)
                                                <flux:select.option :value="$key" wire:key="res-{{ $i }}-{{ $key }}">{{ $label }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" />
                                    </div>

                                    @if ($item['group_name'])
                                        <flux:text size="xs" class="text-zinc-500">{{ $item['group_name'] }}</flux:text>
                                    @endif

                                    <flux:input wire:model="items.{{ $i }}.notes" size="sm" placeholder="Technician note / observation" />

                                    {{-- Before / After photos --}}
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                                        <div class="space-y-1">
                                            <flux:text size="xs" class="font-medium text-zinc-600 dark:text-zinc-400">Before</flux:text>
                                            <div class="flex items-center gap-2">
                                                @if (! empty($item['before_photo_path']))
                                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item['before_photo_path']) }}" alt="Before" class="size-12 rounded object-cover border border-zinc-200 dark:border-zinc-800" />
                                                    <flux:button type="button" size="xs" variant="ghost" icon="trash" wire:click="clearItemPhoto({{ $i }}, 'before')" />
                                                @elseif (! empty($itemBeforeFiles[$i]))
                                                    <img src="{{ $itemBeforeFiles[$i]->temporaryUrl() }}" alt="" class="size-12 rounded object-cover border border-zinc-200 dark:border-zinc-800" />
                                                @endif
                                                <flux:input type="file" wire:model="itemBeforeFiles.{{ $i }}" accept="image/*" size="sm" class:input="text-xs" />
                                            </div>
                                            <flux:error name="itemBeforeFiles.{{ $i }}" />
                                        </div>
                                        <div class="space-y-1">
                                            <flux:text size="xs" class="font-medium text-zinc-600 dark:text-zinc-400">After</flux:text>
                                            <div class="flex items-center gap-2">
                                                @if (! empty($item['after_photo_path']))
                                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item['after_photo_path']) }}" alt="After" class="size-12 rounded object-cover border border-zinc-200 dark:border-zinc-800" />
                                                    <flux:button type="button" size="xs" variant="ghost" icon="trash" wire:click="clearItemPhoto({{ $i }}, 'after')" />
                                                @elseif (! empty($itemAfterFiles[$i]))
                                                    <img src="{{ $itemAfterFiles[$i]->temporaryUrl() }}" alt="" class="size-12 rounded object-cover border border-zinc-200 dark:border-zinc-800" />
                                                @endif
                                                <flux:input type="file" wire:model="itemAfterFiles.{{ $i }}" accept="image/*" size="sm" class:input="text-xs" />
                                            </div>
                                            <flux:error name="itemAfterFiles.{{ $i }}" />
                                        </div>
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
                            <flux:text size="sm" class="mt-1 text-zinc-500">Move the order through its lifecycle. Assigned / Started / Ended stamps are set automatically.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:select wire:model="status" variant="listbox" label="Status" required>
                                    @foreach (VehicleInspectionOrder::statuses() as $key => $label)
                                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="completion_type" variant="listbox" clearable label="Completion Type" placeholder="On completion…">
                                    @foreach (VehicleInspectionOrder::completionTypes() as $key => $label)
                                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <flux:select wire:model="hold_reason_id" variant="listbox" searchable clearable label="Hold Reason" placeholder="If on hold…">
                                    @foreach ($this->holdReasons as $r)
                                        <flux:select.option :value="$r->id" wire:key="hr-{{ $r->id }}">{{ $r->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
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

                            <div class="flex items-center justify-between">
                                <flux:heading size="sm">Pause / Resume Log</flux:heading>
                                <flux:button type="button" size="xs" variant="ghost" icon="plus" wire:click="addPause">Add pause</flux:button>
                            </div>
                            @if (count($pauses) === 0)
                                <flux:text size="sm" class="text-zinc-500">No pauses logged.</flux:text>
                            @else
                                <div class="space-y-2">
                                    @foreach ($pauses as $i => $pause)
                                        <div wire:key="pause-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_1.5fr_auto] gap-2 items-end p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                                            <flux:input type="datetime-local" wire:model="pauses.{{ $i }}.paused_at" size="sm" label="Paused" />
                                            <flux:input type="datetime-local" wire:model="pauses.{{ $i }}.resumed_at" size="sm" label="Resumed" />
                                            <flux:select wire:model="pauses.{{ $i }}.hold_reason_id" variant="listbox" searchable clearable size="sm" label="Reason" placeholder="Reason…">
                                                @foreach ($this->holdReasons as $r)
                                                    <flux:select.option :value="$r->id" wire:key="phr-{{ $i }}-{{ $r->id }}">{{ $r->name }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removePause({{ $i }})" />
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
