{{-- Dept-wise quick-add of services: one box PER selected department, its
     frequent job descriptions grouped by service type, plus a shared
     search-or-type box for everything else. Backed by the PicksQuickServices
     trait on the host component; Appointment (multi-dept) and Pickup/Drop
     (single dept) both render this same block. --}}
@if ($this->frequentServiceGroupsByDepartment->isEmpty() && $this->otherJobDescriptions->isEmpty())
    <flux:callout variant="secondary" icon="information-circle" inline>
        <flux:callout.text>Pick a department to see its jobs.</flux:callout.text>
    </flux:callout>
@else
    {{-- Children bind straight into the array with wire:model — the DOM owns a
         checkbox's `checked`, so deriving it from the server desyncs under fast
         clicks. Group headings and counts are computed in Alpine off $wire, so
         they stay instant and never disagree with the boxes underneath. --}}
    <div class="space-y-4"
        x-data="{
            ids() { return ($wire.selectedServiceIds ?? []).map(String) },
            has(id) { return this.ids().includes(String(id)) },
            allOf(group) { return group.length > 0 && group.every(id => this.has(id)) },
            someOf(group) { return group.some(id => this.has(id)) && ! this.allOf(group) },
            toggleGroup(group) {
                const keep = this.ids().filter(id => ! group.map(String).includes(id))
                $wire.selectedServiceIds = this.allOf(group)
                    ? keep
                    : [...keep, ...group.map(String)]
            },
            countIn(box) { return this.ids().filter(id => box.map(String).includes(id)).length },
        }">
        @foreach ($this->frequentServiceGroupsByDepartment as $dept => $groups)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden"
                wire:key="dept-box-{{ Str::slug($dept) }}"
                x-data="{ boxIds: @js($groups->flatten()->pluck('id')->map(fn ($id) => (string) $id)->values()) }">
                <div class="flex items-center justify-between gap-3 px-3 py-2 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm">Job Description ({{ $dept }})</flux:heading>
                    <flux:badge size="sm" color="lime" x-show="countIn(boxIds) > 0" x-cloak>
                        <span x-text="countIn(boxIds)"></span>&nbsp;selected
                    </flux:badge>
                    <flux:badge size="sm" color="zinc" x-show="countIn(boxIds) === 0">None selected</flux:badge>
                </div>

                @foreach ($groups as $group => $jobs)
                    <div class="px-3 py-2.5 border-b border-zinc-100 dark:border-zinc-800 last:border-b-0"
                        wire:key="grp-{{ Str::slug($dept) }}-{{ Str::slug($group) }}"
                        x-data="{ group: @js($jobs->pluck('id')->map(fn ($id) => (string) $id)->values()) }">
                        <flux:checkbox
                            :label="$group"
                            class="font-medium"
                            x-bind:checked="allOf(group)"
                            x-bind:indeterminate="someOf(group)"
                            x-on:change.stop="toggleGroup(group)"
                        />
                        <div class="mt-1.5 pl-6 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-x-4 gap-y-1">
                            @foreach ($jobs as $job)
                                <flux:checkbox
                                    wire:key="qs-{{ $job->id }}"
                                    wire:model="selectedServiceIds"
                                    value="{{ $job->id }}"
                                    :label="$job->name"
                                />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach

        {{-- Anything not common enough to earn a checkbox: pick it from the
             master, or type it when the master has not caught up. --}}
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="flex items-center justify-between gap-2 px-3 py-2 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm">Requested Repairs (other than above)</flux:heading>
                <div class="flex items-center gap-1">
                    @can('job_description_master.create')
                        <flux:tooltip content="Add a new Job Description to the master">
                            <flux:button size="xs" icon="plus" variant="ghost" type="button" wire:click="openJobDescriptionQuickAdd" />
                        </flux:tooltip>
                    @endcan
                    @can('job_description_master.view')
                        <flux:tooltip content="Open Job Description Master in a new tab">
                            <flux:button size="xs" icon="arrow-top-right-on-square" variant="ghost" type="button"
                                :href="route('job-description-master.index', ['cat' => 'general'])" target="_blank" />
                        </flux:tooltip>
                    @endcan
                </div>
            </div>
            <div class="p-3 space-y-3">
                @if ($this->otherJobDescriptions->isNotEmpty())
                    <flux:select
                        wire:model.live="requestedRepairIds"
                        variant="listbox"
                        multiple
                        searchable
                        clearable
                        placeholder="Search other jobs…"
                    >
                        @foreach ($this->otherJobDescriptions as $job)
                            <flux:select.option :value="$job->id" wire:key="oj-{{ $job->id }}">{{ $job->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <div class="flex items-end gap-2">
                    <div class="flex-1 min-w-0">
                        <flux:input
                            wire:model="manualRepairInput"
                            wire:keydown.enter.prevent="addManualRepair"
                            placeholder="Or type something the list doesn't have…"
                        />
                    </div>
                    <flux:button type="button" variant="ghost" icon="plus" wire:click="addManualRepair">Add</flux:button>
                </div>
                <flux:text size="xs" class="text-zinc-500 -mt-1">
                    “Add” keeps it on this booking only; the + above files it into the Job Description master.
                </flux:text>

                @if ($manualRepairs)
                    <div class="flex flex-wrap gap-2">
                        @foreach ($manualRepairs as $i => $manual)
                            <flux:badge size="sm" wire:key="mr-{{ $i }}">
                                {{ $manual }}
                                <flux:badge.close wire:click="removeManualRepair({{ $i }})" />
                            </flux:badge>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif

{{-- Quick-add a Job Description without leaving the booking. Opens pre-filled
     with whatever was typed in the manual box. --}}
<flux:modal name="job-description-quick-add" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">New Job Description</flux:heading>
            <flux:subheading>Filed in the master — every future booking can pick it.</flux:subheading>
        </div>

        <flux:input wire:model="jdQuickName" label="Job Description" placeholder="e.g. UNDERBODY COATING" required />

        <flux:select wire:model="jdQuickServiceTypeId" variant="listbox" searchable label="Service Type" placeholder="Where does it belong…" required>
            @foreach ($this->jdQuickServiceTypes as $st)
                <flux:select.option :value="$st->id" wire:key="jdq-st-{{ $st->id }}">
                    {{ $st->name }}{{ $st->workshopDepartment ? ' · '.$st->workshopDepartment->name : '' }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model="jdQuickCategory" variant="listbox" label="Category" required>
            <flux:select.option value="general">General — shows in the Requested Repairs picker</flux:select.option>
            <flux:select.option value="frequent">Frequent — earns a checkbox in the checklist</flux:select.option>
        </flux:select>

        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" wire:click="createJobDescription">Save &amp; select</flux:button>
        </div>
    </div>
</flux:modal>
