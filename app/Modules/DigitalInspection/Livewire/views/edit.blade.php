<div>
    <form wire:submit="save" class="max-w-5xl">
        <div class="mb-8 flex items-start justify-between gap-4">
            <div>
                <flux:link :href="route('digital-inspection.index')" variant="ghost" class="text-xs">
                    <flux:icon.chevron-left class="inline size-3 -mt-0.5" />
                    Digital Inspections
                </flux:link>
                <flux:heading size="xl" level="1" class="mt-1">
                    {{ $editingId ? 'Inspection '.$inspection_no : 'New Digital Inspection' }}
                </flux:heading>
            </div>
            @if ($editingId)
                <flux:badge :color="match ($status) {
                    'pending' => 'amber', 'wip' => 'blue', 'completed' => 'lime',
                    'approved' => 'green', 'rejected' => 'red', 'cancelled' => 'zinc',
                    default => 'zinc',
                }" size="lg">{{ \App\Modules\DigitalInspection\Models\DigitalInspection::statuses()[$status] }}</flux:badge>
            @endif
        </div>

        <flux:separator />

        {{-- HEADER --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Inspection Setup</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Pick the job card and template, assign a technician.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:select wire:model="job_card_id" variant="listbox" searchable label="Job Card" placeholder="Pick an open job card…" required>
                    @foreach ($this->jobCards as $jc)
                        <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">
                            {{ $jc->job_card_no }} — {{ trim($jc->customer?->first_name.' '.($jc->customer?->last_name ?? '')) }}
                            @if ($jc->customerVehicle) · {{ $jc->customerVehicle->registration_no }} @endif
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model.live="inspection_template_id" variant="listbox" searchable label="Template" placeholder="Pick a template…" required>
                        @foreach ($this->templates as $t)
                            <flux:select.option :value="$t->id" wire:key="tpl-{{ $t->id }}">{{ $t->name }} <span class="text-xs text-zinc-500">({{ strtoupper($t->applies_to) }})</span></flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="status" variant="listbox" label="Status" required>
                        @foreach (\App\Modules\DigitalInspection\Models\DigitalInspection::statuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="assigned_technician_id" variant="listbox" searchable clearable label="Technician" placeholder="Pick a technician…">
                        @foreach ($this->technicians as $e)
                            <flux:select.option :value="$e->id" wire:key="tech-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="floor_incharge_id" variant="listbox" searchable clearable label="Floor In-charge" placeholder="Optional…">
                        @foreach ($this->technicians as $e)
                            <flux:select.option :value="$e->id" wire:key="fi-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- CHECKLIST --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Checklist</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">
                    Walk each item: set a <span class="font-medium text-zinc-700 dark:text-zinc-300">result</span>, then add a recommendation, severity, observation and photo as needed.
                </flux:text>
            </div>
            <div class="space-y-6 min-w-0">
                {{-- Quick-pick standard observations (faster entry) --}}
                <datalist id="di-standard-observations">
                    @foreach ($this->standardObservations as $obs)
                        <option value="{{ $obs }}"></option>
                    @endforeach
                </datalist>

                @if (count($items) === 0)
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                        Pick a template above and the checklist will load here.
                    </div>
                @else
                    @php
                        $grouped = collect($items)->groupBy('group_name');
                    @endphp
                    @foreach ($grouped as $groupName => $groupItems)
                        <div class="space-y-3">
                            <div class="text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ $groupName ?? 'General' }}</div>
                            @foreach ($groupItems as $item)
                                @php($i = collect($items)->search(fn ($x) => $x['inspection_item_id'] === $item['inspection_item_id']))
                                @php($itemId = (int) $item['inspection_item_id'])
                                @php($imgUrl = ! empty($itemImages[$itemId]) ? $itemImages[$itemId]->temporaryUrl() : (! empty($item['image_path']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($item['image_path']) : null))
                                <div wire:key="item-row-{{ $itemId }}" class="rounded-lg border border-zinc-200 dark:border-zinc-800 p-4 space-y-3">
                                    {{-- Title + result --}}
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="font-medium text-sm truncate">{{ $item['name'] }}</div>
                                            <flux:text size="xs" class="text-zinc-400">{{ \Illuminate\Support\Str::headline($item['check_type']) }}</flux:text>
                                        </div>
                                        <div class="w-44 shrink-0">
                                            <flux:select wire:model="items.{{ $i }}.outcome" variant="listbox" size="sm">
                                                @foreach (\App\Modules\DigitalInspection\Models\DigitalInspection::outcomes() as $key => $label)
                                                    <flux:select.option :value="$key" wire:key="oc-{{ $i }}-{{ $key }}">{{ $label }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </div>
                                    </div>

                                    {{-- Recommendation + severity --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <flux:select wire:model="items.{{ $i }}.recommendation" variant="listbox" size="sm" clearable placeholder="Recommendation…">
                                            @foreach (\App\Modules\DigitalInspection\Models\DigitalInspection::recommendations() as $key => $label)
                                                <flux:select.option :value="$key" wire:key="rec-{{ $i }}-{{ $key }}">{{ $label }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:select wire:model="items.{{ $i }}.severity" variant="listbox" size="sm" clearable placeholder="Severity…">
                                            @foreach (\App\Modules\DigitalInspection\Models\DigitalInspection::severities() as $key => $label)
                                                <flux:select.option :value="$key" wire:key="sev-{{ $i }}-{{ $key }}">{{ $label }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>

                                    {{-- Observation (quick-pick) + notes --}}
                                    <flux:input wire:model="items.{{ $i }}.observation" size="sm" list="di-standard-observations" placeholder="Observation — pick a standard comment or type…" />
                                    <flux:input wire:model="items.{{ $i }}.notes" size="sm" placeholder="Notes / measurement (optional)" />

                                    {{-- Photo evidence --}}
                                    <div class="flex items-center gap-3 pt-1">
                                        @if ($imgUrl)
                                            <img src="{{ $imgUrl }}" alt="Evidence" class="size-12 rounded object-cover border border-zinc-200 dark:border-zinc-800" />
                                        @endif
                                        <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-zinc-200 dark:border-zinc-700 px-2.5 py-1.5 text-xs font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                            <input type="file" class="sr-only" wire:model="itemImages.{{ $itemId }}" accept="image/*" />
                                            <flux:icon.camera variant="micro" class="size-3.5" />
                                            {{ $imgUrl ? 'Retake photo' : 'Add photo' }}
                                        </label>
                                        @if (! empty($item['image_path']))
                                            <flux:button type="button" size="xs" variant="ghost" icon="trash" wire:click="removeItemImage({{ $itemId }})">Remove</flux:button>
                                        @elseif (! empty($itemImages[$itemId]))
                                            <flux:text size="xs" class="text-zinc-400">Saved on submit</flux:text>
                                        @endif
                                    </div>
                                    <flux:error name="itemImages.{{ $itemId }}" />
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif
            </div>
        </section>

        <flux:separator />

        {{-- SUMMARY --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Summary</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Wrap-up notes for the advisor when the inspection completes.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:textarea
                    wire:model="summary_notes"
                    label="Summary Notes"
                    placeholder="Overall vehicle condition + key findings."
                    rows="3"
                />
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('digital-inspection.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save Changes' : 'Create Inspection' }}</flux:button>
        </div>
    </form>
</div>
