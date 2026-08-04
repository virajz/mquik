@php($SI = \App\Modules\SurveyorInspection\Models\SurveyorInspection::class)
@php($ITEM = \App\Modules\SurveyorInspection\Models\SurveyorInspectionItem::class)
@php($ATT = \App\Modules\SurveyorInspection\Models\SurveyorInspectionAttachment::class)
<div>
    <form wire:submit="save" class="max-w-3xl">
        <div class="mb-8">
            <flux:link :href="route('surveyor-inspection.index')" variant="ghost" class="text-xs">
                <flux:icon.chevron-left class="inline size-3 -mt-0.5" /> Surveyor Inspection
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editingId ? ($inspection_no ?: 'Edit Inspection') : 'New Surveyor Inspection' }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Record the surveyor's findings and per-line decisions.</flux:text>
        </div>

        <flux:separator />

        {{-- REFERENCES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">References</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Job card, estimate and insurer.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="job_card_id" variant="listbox" searchable clearable :filter="false" label="Job Card" placeholder="Link a job card…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="jobCardSearch" placeholder="Search job card…" /></x-slot>
                        @foreach ($this->jobCards as $jc)
                            <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="sales_estimate_id" variant="listbox" searchable clearable :filter="false" label="Estimate" placeholder="Link an estimate…">
                        <x-slot name="search"><flux:select.search wire:model.live.debounce.250ms="estimateSearch" placeholder="Search estimate…" /></x-slot>
                        @foreach ($this->estimates as $es)
                            <flux:select.option :value="$es->id" wire:key="es-{{ $es->id }}">{{ $es->estimate_no }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:select wire:model="insurance_company_id" variant="listbox" searchable clearable label="Insurance Company" placeholder="Insurer…" class="md:max-w-sm">
                    @foreach ($this->companies as $co)
                        <flux:select.option :value="$co->id" wire:key="co-{{ $co->id }}">{{ $co->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </section>

        <flux:separator />

        {{-- SURVEYOR --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Surveyor & Type</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Who surveyed and what kind of survey.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:input wire:model="surveyor_name" label="Surveyor / Assessor" placeholder="Name" />
                    <flux:input wire:model="surveyor_phone" label="Surveyor Phone" mask="99999 99999" placeholder="Optional" inputmode="numeric" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="survey_type" variant="listbox" clearable label="Survey Type" placeholder="Preliminary / Final / …">
                        @foreach ($SI::surveyTypes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="surveyor_approval" variant="listbox" clearable label="Surveyor Approval" placeholder="Overall outcome…">
                        @foreach ($SI::approvalOutcomes() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </section>

        <flux:separator />

        {{-- LINES --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Assessed Lines</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Spares/labour with the surveyor's per-line decision.</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end gap-2">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem('spare')">Spare</flux:button>
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addItem('labour')">Labour</flux:button>
                </div>

                @forelse ($items as $i => $item)
                    <div wire:key="item-{{ $i }}" class="space-y-2 p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <div class="grid grid-cols-1 md:grid-cols-[90px_1fr_auto] gap-2 items-end">
                            <flux:badge size="sm" :color="$item['line_type'] === 'labour' ? 'purple' : 'sky'">{{ ucfirst($item['line_type']) }}</flux:badge>
                            @if ($item['line_type'] === 'spare')
                                <flux:select wire:model="items.{{ $i }}.spare_id" variant="listbox" searchable size="sm" label="Spare" placeholder="Pick a spare…">
                                    @foreach ($this->spares as $s)
                                        <flux:select.option :value="$s->id" wire:key="ss-{{ $i }}-{{ $s->id }}">{{ $s->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @else
                                <flux:select wire:model="items.{{ $i }}.labour_id" variant="listbox" searchable size="sm" label="Labour" placeholder="Pick a labour…">
                                    @foreach ($this->labours as $l)
                                        <flux:select.option :value="$l->id" wire:key="sl-{{ $i }}-{{ $l->id }}">{{ $l->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @endif
                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" class="h-9!" />
                        </div>
                        <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Line description (required)" required />
                        <flux:error name="items.{{ $i }}.description" />
                        <div class="grid grid-cols-2 gap-2 md:max-w-md">
                            <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" min="0.01" size="sm" label="Qty" class:input="text-right font-mono" />
                            <flux:select wire:model="items.{{ $i }}.line_approval" variant="listbox" size="sm" label="Decision" placeholder="Repair / Replace…">
                                @foreach ($ITEM::lineApprovals() as $key => $label)
                                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                        Add the spare/labour lines the surveyor assessed.
                    </div>
                @endforelse
            </div>
        </section>

        <flux:separator />

        {{-- OUTCOME --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Outcome & Status</flux:heading>
            </div>
            <div class="space-y-4 min-w-0" x-data>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model="not_covered_reason" variant="listbox" clearable label="Not Covered Reason" placeholder="If any not covered…">
                        @foreach ($SI::notCoveredReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="rejection_reason" variant="listbox" clearable label="No-Claim / Rejection Reason" placeholder="If rejected…">
                        @foreach ($SI::rejectionReasons() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <flux:select wire:model.live="status" variant="listbox" label="Inspection Status" required>
                        @foreach ($SI::statuses() as $key => $label)
                            <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div x-show="$wire.status === 'completed'" x-cloak>
                        <div class="grid grid-cols-2 gap-2">
                            <flux:date-picker wire:model="surveyed_at" label="Surveyed At" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                            <flux:time-picker wire:model="surveyed_at_time" label="Time" />
                        </div>
                        <flux:error name="surveyed_at" />
                    </div>
                </div>
                <flux:textarea wire:model="notes" label="Notes" placeholder="Surveyor remarks." rows="2" />
            </div>
        </section>

        <flux:separator />

        {{-- ATTACHMENTS --}}
        <section class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Attachments</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Approval note or survey photos (PDF/image).</flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                <div class="flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addAttachment">Add file</flux:button>
                </div>
                @forelse ($attachments as $i => $att)
                    <div wire:key="att-{{ $i }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2 items-end p-3 rounded-md border border-zinc-200 dark:border-zinc-800">
                        <flux:select wire:model="attachments.{{ $i }}.attachment_type" variant="listbox" size="sm" clearable label="Type" placeholder="Type…">
                            @foreach ($ATT::attachmentTypes() as $key => $label)
                                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <div>
                            <flux:input type="file" wire:model="attachmentFiles.{{ $i }}" size="sm" label="File" accept=".jpg,.jpeg,.png,.webp,.pdf" />
                            @if (! empty($att['path']))
                                <flux:text size="sm" class="text-zinc-500 mt-1">Current: {{ $att['original_name'] ?? basename($att['path']) }}</flux:text>
                            @endif
                            <flux:error name="attachmentFiles.{{ $i }}" />
                        </div>
                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeAttachment({{ $i }})" class="h-9!" />
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                        No files yet. Click <span class="font-medium">Add file</span> to attach the approval note.
                    </div>
                @endforelse
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('surveyor-inspection.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save changes' : 'Create inspection' }}</flux:button>
        </div>
    </form>
</div>
