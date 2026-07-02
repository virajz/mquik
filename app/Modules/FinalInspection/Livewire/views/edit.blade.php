@use(App\Modules\FinalInspection\Models\FinalInspection)
<div class="max-w-7xl">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:link :href="route('final-inspection.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Final Inspections
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? 'Final Inspection '.$inspection_no : 'New Final Inspection' }}</flux:heading>
        </div>
        @if ($editingId)
            <flux:badge :color="match ($status) {
                'completed' => 'lime', 'in_progress' => 'blue', 'on_hold' => 'amber', 'rework' => 'orange', 'cancelled' => 'red', default => 'zinc',
            }" size="lg">{{ FinalInspection::statuses()[$status] }}</flux:badge>
        @endif
    </div>

    <flux:separator class="mb-6" />

    <form wire:submit="save">
        @if (! $editingId)
            @include('final-inspection::partials.section-details')
            <flux:callout class="mt-6" icon="information-circle" variant="secondary">
                <flux:callout.text>Pick a template now to snapshot its checkpoints — the checklist &amp; photos unlock after you create the inspection.</flux:callout.text>
            </flux:callout>
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('final-inspection.index')" variant="ghost" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Create Inspection</flux:button>
            </div>
        @else
            <flux:tab.group>
                <flux:tabs wire:model="activeTab">
                    <flux:tab name="details" icon="clipboard-document-list">Details</flux:tab>
                    <flux:tab name="checklist" icon="list-bullet">Checklist &amp; Photos <flux:badge size="sm" class="ml-1">{{ count($items) }}</flux:badge></flux:tab>
                    <flux:tab name="closeout" icon="check-badge">Time &amp; Close-out</flux:tab>
                </flux:tabs>

                <flux:tab.panel name="details" class="pt-6">
                    @include('final-inspection::partials.section-details')
                </flux:tab.panel>

                {{-- CHECKLIST --}}
                <flux:tab.panel name="checklist" class="pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="lg">Checklist &amp; Photo Evidence</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Result, recommendation, severity, observation and before / after / damage photos per checkpoint.</flux:text>
                        </div>
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem">Add item</flux:button>
                    </div>

                    <datalist id="fi-standard-observations">
                        @foreach ($this->standardObservations as $obs)
                            <option value="{{ $obs }}"></option>
                        @endforeach
                    </datalist>

                    @if (count($items) === 0)
                        <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                            Pick a template on the Details tab, or add checkpoints manually.
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($items as $i => $item)
                                <div wire:key="fi-item-{{ $i }}" class="rounded-lg border border-zinc-200 dark:border-zinc-800 p-4 space-y-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <flux:input wire:model="items.{{ $i }}.label" size="sm" placeholder="Checkpoint" />
                                            @if ($item['group_name'])<flux:text size="xs" class="text-zinc-400 mt-1">{{ $item['group_name'] }}</flux:text>@endif
                                        </div>
                                        <div class="w-44 shrink-0">
                                            <flux:select wire:model="items.{{ $i }}.result" variant="listbox" size="sm">
                                                @foreach (FinalInspection::results() as $k => $l)
                                                    <flux:select.option :value="$k" wire:key="res-{{ $i }}-{{ $k }}">{{ $l }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </div>
                                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" />
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <flux:select wire:model="items.{{ $i }}.recommendation" variant="listbox" size="sm" clearable placeholder="Recommendation…">
                                            @foreach (FinalInspection::itemRecommendations() as $k => $l)
                                                <flux:select.option :value="$k" wire:key="rec-{{ $i }}-{{ $k }}">{{ $l }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:select wire:model="items.{{ $i }}.severity" variant="listbox" size="sm" clearable placeholder="Severity…">
                                            @foreach (FinalInspection::severities() as $k => $l)
                                                <flux:select.option :value="$k" wire:key="sev-{{ $i }}-{{ $k }}">{{ $l }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>

                                    <flux:input wire:model="items.{{ $i }}.observation" size="sm" list="fi-standard-observations" placeholder="Observation — pick a standard comment or type…" />

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        @foreach (['before' => 'Before', 'after' => 'After', 'damage' => 'Damage Proof'] as $which => $lbl)
                                            @php($path = $item[$which.'_photo_path'] ?? null)
                                            @php($staged = $which === 'before' ? ($itemBeforeFiles[$i] ?? null) : ($which === 'after' ? ($itemAfterFiles[$i] ?? null) : ($itemDamageFiles[$i] ?? null)))
                                            @php($url = $staged ? $staged->temporaryUrl() : ($path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null))
                                            <div class="space-y-1">
                                                <flux:text size="xs" class="font-medium text-zinc-600 dark:text-zinc-400">{{ $lbl }}</flux:text>
                                                <div class="flex items-center gap-2">
                                                    @if ($url)<img src="{{ $url }}" alt="{{ $lbl }}" class="size-12 rounded object-cover border border-zinc-200 dark:border-zinc-800" />@endif
                                                    <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-zinc-200 dark:border-zinc-700 px-2.5 py-1.5 text-xs font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                                        <input type="file" class="sr-only" wire:model="item{{ ucfirst($which) }}Files.{{ $i }}" accept="image/*" />
                                                        <flux:icon.camera variant="micro" class="size-3.5" /> {{ $url ? 'Retake' : 'Add' }}
                                                    </label>
                                                    @if ($path)<flux:button type="button" size="xs" variant="ghost" icon="trash" wire:click="clearItemPhoto({{ $i }}, '{{ $which }}')" />@endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </flux:tab.panel>

                {{-- TIME & CLOSE-OUT --}}
                <flux:tab.panel name="closeout" class="pt-6">
                    <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
                        <div>
                            <flux:heading size="lg">Time &amp; Close-out</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Completion, rework, additional-work recommendation and time tracking.</flux:text>
                        </div>
                        <div class="space-y-4 min-w-0">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <flux:select wire:model="status" variant="listbox" label="Status" required>
                                    @foreach (FinalInspection::statuses() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="work_completion_type" variant="listbox" clearable label="Work Completion" placeholder="—">
                                    @foreach (FinalInspection::completionTypes() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="additional_work_recommendation" variant="listbox" clearable label="Additional Work" placeholder="—">
                                    @foreach (FinalInspection::recommendationTypes() as $k => $l)
                                        <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            <flux:select wire:model="rework_reason_id" variant="listbox" searchable clearable label="Rework Reason" placeholder="If rework needed…" class="md:max-w-sm">
                                @foreach ($this->reworkReasons as $r)
                                    <flux:select.option :value="$r->id" wire:key="rr-{{ $r->id }}">{{ $r->name }}</flux:select.option>
                                @endforeach
                            </flux:select>

                            <flux:separator variant="subtle" />
                            <div class="flex items-center justify-between">
                                <flux:heading size="sm">Time Log (Pause / Break)</flux:heading>
                                <flux:button type="button" size="xs" variant="ghost" icon="plus" wire:click="addPause">Add pause</flux:button>
                            </div>
                            @if (count($pauses) === 0)
                                <flux:text size="sm" class="text-zinc-500">No pauses logged. Start/End times stamp automatically on status change.</flux:text>
                            @else
                                <div class="space-y-2">
                                    @foreach ($pauses as $i => $pause)
                                        <div wire:key="fi-pause-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_1.5fr_auto] gap-2 items-end p-2 rounded-md border border-zinc-200 dark:border-zinc-800">
                                            <flux:input type="datetime-local" wire:model="pauses.{{ $i }}.paused_at" size="sm" label="Paused" />
                                            <flux:input type="datetime-local" wire:model="pauses.{{ $i }}.resumed_at" size="sm" label="Resumed" />
                                            <flux:input wire:model="pauses.{{ $i }}.notes" size="sm" label="Reason / notes" />
                                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removePause({{ $i }})" />
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <flux:textarea wire:model="summary_notes" label="Summary Notes" rows="3" placeholder="Overall pre-delivery verdict + key findings." />
                        </div>
                    </section>
                </flux:tab.panel>
            </flux:tab.group>

            <flux:separator class="mt-4" />
            <div class="flex items-center justify-end gap-2 py-6">
                <flux:button :href="route('final-inspection.index')" variant="ghost" wire:navigate>Close</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Save Changes</flux:button>
            </div>
        @endif
    </form>
</div>
