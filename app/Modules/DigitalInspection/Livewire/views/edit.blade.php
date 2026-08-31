<div>
    <form wire:submit="save" novalidate class="max-w-5xl">
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
                }" size="lg">{{ \App\Modules\DigitalInspection\Models\DigitalInspection::allStatuses()[$status] ?? $status }}</flux:badge>
            @endif
        </div>

        <flux:separator />

        {{-- HEADER --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Inspection Setup</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">Pick the job card and template, assign a technician. The rest comes off the card.</flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                {{-- Only cards still in the workshop, shown by model and plate:
                     the model tells the technician what they are walking up to. --}}
                <flux:select wire:model.live="job_card_id" variant="listbox" searchable label="Job Card" placeholder="Pick a pending job card…" required>
                    @foreach ($this->jobCards as $jc)
                        <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">
                            {{ $jc->job_card_no }}
                            @if ($jc->customerVehicle?->model) — {{ $jc->customerVehicle->model->name }} @endif
                            @if ($jc->customerVehicle) · {{ \App\Support\RegistrationNumber::format($jc->customerVehicle->registration_no) }} @endif
                        </flux:select.option>
                    @endforeach
                </flux:select>

                {{-- Everything the card already knows. Read-only: an inspection
                     that disagreed with its job card would be worse than useless. --}}
                @if ($this->jobCardContext)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                        @foreach ([
                            'Advisor' => $this->jobCardContext->advisor?->name,
                            'Department' => $this->jobCardContext->workshopDepartment?->name,
                            'Service Type' => $this->jobCardContext->serviceType?->name,
                            'Variant' => $this->jobCardContext->customerVehicle?->variant?->name,
                            'Year' => $this->jobCardContext->customerVehicle?->year_of_manufacture,
                            'Odometer' => $this->jobCardContext->customerVehicle?->odometer_km
                                ? number_format((float) $this->jobCardContext->customerVehicle->odometer_km).' km'
                                : null,
                        ] as $label => $value)
                            <div>
                                <div class="text-xs text-zinc-500">{{ $label }}</div>
                                <div class="mt-0.5 font-medium">{{ $value ?: '—' }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model.live="inspection_template_id" variant="listbox" searchable label="Template" placeholder="Pick a template…" required>
                        @foreach ($this->templates as $t)
                            <flux:select.option :value="$t->id" wire:key="tpl-{{ $t->id }}">{{ $t->name }} <span class="text-xs text-zinc-500">({{ strtoupper($t->applies_to) }})</span></flux:select.option>
                        @endforeach
                    </flux:select>

                    {{-- Status follows the checklist; it is not something to pick. --}}
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <div class="flex items-center gap-2 h-10">
                            <flux:badge size="sm" :color="match ($status) {
                                'pending' => 'amber', 'wip' => 'blue', 'completed' => 'lime',
                                'cancelled' => 'zinc', default => 'zinc',
                            }">{{ \App\Modules\DigitalInspection\Models\DigitalInspection::allStatuses()[$status] ?? $status }}</flux:badge>
                            <flux:text size="sm" class="text-zinc-500">{{ $this->statusExplanation }}</flux:text>
                        </div>
                    </flux:field>
                </div>

                {{-- Technician TAT: whose inspection it was and how long it took.
                     Both stamps follow the checklist, so the number cannot drift
                     from the sheet it measures. --}}
                @if ($editingId)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        @foreach ([
                            'Technician' => $this->assignedTechnicianName,
                            'Started' => $this->startedAt?->format('d/m/Y h:i A'),
                            'Completed' => $this->completedAt?->format('d/m/Y h:i A'),
                            'TAT' => $this->turnaround,
                        ] as $label => $value)
                            <div>
                                <div class="text-xs text-zinc-500">{{ $label }}</div>
                                <div class="mt-0.5 font-medium tabular-nums">{{ $value ?: '—' }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="assigned_technician_id" variant="listbox" searchable clearable required label="Technician" placeholder="Pick a technician…">
                        @foreach ($this->technicians as $e)
                            <flux:select.option :value="$e->id" wire:key="tech-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    {{-- Floor or front desk: either owns the sheet, so either
                         satisfies the rule and neither does not. --}}
                    <flux:select wire:model.live="floor_incharge_id" variant="listbox" searchable clearable
                        label="Floor In-charge" :required="! $advisor_id"
                        :placeholder="$advisor_id ? 'Optional — advisor owns this' : 'Pick a floor in-charge…'">
                        @foreach ($this->floorIncharges as $e)
                            <flux:select.option :value="$e->id" wire:key="fi-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model.live="advisor_id" variant="listbox" searchable clearable
                        label="Advisor" :required="! $floor_incharge_id"
                        :placeholder="$floor_incharge_id ? 'Optional — floor owns this' : 'Pick an advisor…'">
                        @foreach ($this->advisors as $e)
                            <flux:select.option :value="$e->id" wire:key="ad-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:error name="floor_incharge_id" />
                <flux:error name="advisor_id" />
            </div>
        </section>

        <flux:separator />

        {{-- CHECKLIST --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Checklist</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">
                    Walk each item: set an <span class="font-medium text-zinc-700 dark:text-zinc-300">action type</span>, then add the recommendation, severity, description and photo as needed.
                </flux:text>
            </div>
            <div class="space-y-6 min-w-0">

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
                            <div class="text-base font-bold uppercase tracking-wide text-[var(--color-mq-orange-600)] dark:text-[var(--color-mq-orange-400)]">
                                {{ $groupName ?? 'General' }}
                            </div>
                            @foreach ($groupItems as $item)
                                @php($i = collect($items)->search(fn ($x) => $x['inspection_item_id'] === $item['inspection_item_id']))
                                @php($itemId = (int) $item['inspection_item_id'])
                                @php($imgUrl = ! empty($itemImages[$itemId]) ? $itemImages[$itemId]->temporaryUrl() : (! empty($item['image_path']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($item['image_path']) : null))
                                <div wire:key="item-row-{{ $itemId }}" class="rounded-lg border border-zinc-200 dark:border-zinc-800 p-4 space-y-3">
                                    {{-- Title + result --}}
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="font-medium text-sm truncate">{{ $item['name'] }}</div>
                                        </div>
                                        <div class="w-56 shrink-0">
                                            <flux:select wire:model.live="items.{{ $i }}.outcome" variant="listbox" size="sm" required label="Action Type">
                                                @foreach (\App\Modules\DigitalInspection\Models\DigitalInspection::outcomes() as $key => $label)
                                                    <flux:select.option :value="$key" wire:key="oc-{{ $i }}-{{ $key }}">{{ $label }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </div>
                                    </div>

                                    {{-- Recommendation + severity. Both go quiet on
                                         "No Attention": there is nothing to
                                         recommend or rate on a checkpoint that
                                         needs nothing. Severity arrives filled in
                                         from the action type and can be moved. --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <flux:select wire:model="items.{{ $i }}.recommendation" variant="listbox" size="sm" clearable
                                            :disabled="($item['outcome'] ?? null) === 'na'"
                                            :placeholder="($item['outcome'] ?? null) === 'na' ? 'Not applicable' : 'Recommendation…'">
                                            @foreach (\App\Modules\DigitalInspection\Models\DigitalInspection::recommendations() as $key => $label)
                                                <flux:select.option :value="$key" wire:key="rec-{{ $i }}-{{ $key }}">{{ $label }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:select wire:model="items.{{ $i }}.severity" variant="listbox" size="sm" clearable
                                            :disabled="($item['outcome'] ?? null) === 'na'"
                                            :placeholder="($item['outcome'] ?? null) === 'na' ? 'Not applicable' : 'Severity…'">
                                            @foreach (\App\Modules\DigitalInspection\Models\DigitalInspection::severities() as $key => $label)
                                                <flux:select.option :value="$key" wire:key="sev-{{ $i }}-{{ $key }}">{{ $label }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>

                                    {{-- Recommendation Desc: narrow by category,
                                         then tick as many as apply. "Replace pads"
                                         and "skim discs" is one job to a
                                         technician, so this is not a single box. --}}
                                    @if (($item['outcome'] ?? null) !== 'na')
                                        <div class="rounded-md border border-zinc-100 dark:border-zinc-800 p-3 space-y-2">
                                            <flux:text size="xs" class="font-medium text-zinc-600 dark:text-zinc-400">Recommendation Desc</flux:text>
                                            <div class="flex flex-wrap items-end gap-2">
                                                <div class="w-44">
                                                    <flux:select wire:model.live="itemRecCategory.{{ $i }}" variant="listbox" searchable clearable
                                                        size="sm" label="Category" placeholder="All">
                                                        @foreach ($this->recommendationCategories as $cat)
                                                            <flux:select.option :value="$cat->id" wire:key="irc-{{ $i }}-{{ $cat->id }}">{{ $cat->name }}</flux:select.option>
                                                        @endforeach
                                                    </flux:select>
                                                </div>
                                                <div class="w-44">
                                                    <flux:select wire:model.live="itemRecSubCategory.{{ $i }}" variant="listbox" searchable clearable
                                                        size="sm" label="Sub category"
                                                        :disabled="empty($itemRecCategory[$i])"
                                                        :placeholder="empty($itemRecCategory[$i]) ? 'Pick a category' : 'All'">
                                                        @foreach ($this->recommendationSubCategories($itemRecCategory[$i] ?? null) as $sub)
                                                            <flux:select.option :value="$sub->id" wire:key="irs-{{ $i }}-{{ $sub->id }}">{{ $sub->name }}</flux:select.option>
                                                        @endforeach
                                                    </flux:select>
                                                </div>
                                                <flux:spacer />
                                                <flux:button type="button" size="xs" variant="ghost" wire:click="selectAllRecommendations({{ $i }})">Select all</flux:button>
                                                <flux:button type="button" size="xs" variant="ghost" wire:click="clearRecommendations({{ $i }})">Clear</flux:button>
                                                @can('recommendation_description_master.create')
                                                    <flux:tooltip content="Add wording and tick it here">
                                                        <flux:button type="button" size="xs" variant="outline" icon="plus" wire:click="openRecommendationQuickAdd({{ $i }})">Quick add</flux:button>
                                                    </flux:tooltip>
                                                @endcan
                                                @can('recommendation_description_master.view')
                                                    <flux:tooltip content="Open Recommendation Descriptions in a new tab">
                                                        <flux:button type="button" size="xs" variant="ghost" icon="arrow-top-right-on-square"
                                                            :href="route('recommendation-description-master.index')" target="_blank" />
                                                    </flux:tooltip>
                                                @endcan
                                            </div>

                                            @php($options = $this->recommendationOptions($i))
                                            @if ($options->isEmpty())
                                                <flux:text size="xs" class="text-zinc-400">
                                                    Nothing filed under this category yet — use <span class="font-medium">Quick add</span>.
                                                </flux:text>
                                            @else
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1">
                                                    @foreach ($options as $option)
                                                        <flux:checkbox wire:model="itemRecommendations.{{ $i }}" :value="(string) $option->id"
                                                            :label="$option->name" wire:key="ird-{{ $i }}-{{ $option->id }}" />
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Notes --}}
                                    <flux:input wire:model="items.{{ $i }}.notes" size="sm" placeholder="Notes / observation (optional)" />

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
                <flux:text size="sm" class="mt-1 text-zinc-500">
                    Two audiences, two boxes: what the workshop tells itself, and what the customer reads on their copy.
                </flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:textarea
                    wire:model="summary_notes"
                    label="Internal Notes"
                    description="Stays inside the workshop — never printed."
                    placeholder="Overall vehicle condition + key findings."
                    rows="3"
                />

                <flux:textarea
                    wire:model="customer_notes"
                    label="Customer Notes"
                    description="Printed on the customer's copy of the checklist."
                    placeholder="What you want the customer to read and keep."
                    rows="3"
                />
            </div>
        </section>

        <flux:separator />

        {{-- CUSTOMER EXPLANATION & APPROVAL — mirrors the physical checklist. --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Customer Explanation &amp; Approval</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">
                    What the customer was shown, and what they decided. Ticked as it happens, not afterwards.
                </flux:text>
            </div>
            <div class="space-y-4 min-w-0">
                <flux:checkbox.group>
                    <flux:checkbox wire:model="explained_on_lift" label="Explained with car on lift" />
                    <flux:checkbox wire:model="media_shared" label="Photos / videos shared on WhatsApp" />
                    <flux:checkbox wire:model="questions_answered" label="Customer questions answered" />
                </flux:checkbox.group>

                <flux:separator variant="subtle" />

                <flux:radio.group wire:model="customer_approval" label="Customer approval" variant="segmented">
                    @foreach (\App\Modules\DigitalInspection\Models\DigitalInspection::customerApprovals() as $key => $label)
                        <flux:radio :value="$key" :label="$label" wire:key="ca-{{ $key }}" />
                    @endforeach
                </flux:radio.group>
                <flux:error name="customer_approval" />

                @if ($this->customerApprovalAt)
                    <flux:text size="xs" class="text-zinc-500">
                        Recorded {{ $this->customerApprovalAt->format('d/m/Y h:i A') }}
                    </flux:text>
                @endif
            </div>
        </section>

        <flux:separator />

        {{-- INTERNAL CONTROL — three sign-offs, each a person and a moment. --}}
        <section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-8">
            <div>
                <flux:heading size="lg">Internal Control</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">
                    Who put their name to this sheet. Naming someone stamps the time; clearing the name removes it.
                </flux:text>
            </div>
            <div class="space-y-3 min-w-0">
                @foreach ([
                    ['role' => 'technician', 'label' => 'Inspection done by Technician', 'staff' => $this->technicians],
                    ['role' => 'supervisor', 'label' => 'Cross-checked by Floor Supervisor', 'staff' => $this->floorIncharges],
                    ['role' => 'advisor', 'label' => 'Advisor verified &amp; explained', 'staff' => $this->advisors],
                ] as $row)
                    <div wire:key="signoff-{{ $row['role'] }}"
                        class="rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 grid grid-cols-1 md:grid-cols-[1fr_240px_150px] gap-3 items-center">
                        <div class="flex items-center gap-2">
                            <flux:icon :name="$this->signedAt($row['role']) ? 'check-circle' : 'minus-circle'"
                                class="size-4 {{ $this->signedAt($row['role']) ? 'text-lime-500' : 'text-zinc-300 dark:text-zinc-600' }}" />
                            <span class="text-sm font-medium">{!! $row['label'] !!}</span>
                        </div>

                        <flux:select wire:model.live="{{ $row['role'] }}_signed_by_id" variant="listbox" searchable clearable
                            size="sm" placeholder="Not signed…">
                            @foreach ($row['staff'] as $e)
                                <flux:select.option :value="$e->id" wire:key="sg-{{ $row['role'] }}-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:text size="xs" class="text-zinc-500">
                            {{ $this->signedAt($row['role'])?->format('d/m/Y h:i A') ?? '—' }}
                        </flux:text>
                    </div>
                @endforeach
            </div>
        </section>

        <flux:separator />

        <div class="flex items-center justify-end gap-2 py-6">
            <flux:button :href="route('digital-inspection.index')" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ $editingId ? 'Save Changes' : 'Create Inspection' }}</flux:button>
        </div>
    </form>

    <flux:modal name="di-recommendation-quick-add" class="md:w-[28rem]">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">New recommendation wording</flux:heading>
                <flux:subheading>Added to the master and ticked on this checkpoint at once.</flux:subheading>
            </div>

            <flux:select wire:model.live="quickRecCategoryId" variant="listbox" searchable required
                label="Category" placeholder="Pick a category…">
                @foreach ($this->recommendationCategories as $cat)
                    <flux:select.option :value="$cat->id" wire:key="qrc-{{ $cat->id }}">{{ $cat->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="quickRecCategoryId" />

            <flux:select wire:model="quickRecSubCategoryId" variant="listbox" searchable clearable
                label="Sub category" :disabled="! $quickRecCategoryId"
                :placeholder="$quickRecCategoryId ? 'Optional' : 'Pick a category first'">
                @foreach ($this->recommendationSubCategories($quickRecCategoryId) as $sub)
                    <flux:select.option :value="$sub->id" wire:key="qrs-{{ $sub->id }}">{{ $sub->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="quickRecSubCategoryId" />

            <flux:input wire:model="quickRecName" label="Description" required
                placeholder="Replace front brake pads — worn below 3mm" />
            <flux:error name="quickRecName" />

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button variant="primary" wire:click="createRecommendationDescription">Add</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
