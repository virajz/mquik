{{-- Dept-wise quick-add of services: the frequent job descriptions as a
     grouped checklist, everything else via search-or-type. Backed by the
     PicksQuickServices trait on the host component; both Appointment and
     Pickup/Drop render this same block. --}}
@if (! $workshop_department_id)
    <flux:callout variant="secondary" icon="information-circle" inline>
        <flux:callout.text>Pick a department in Service &amp; Routing to see its jobs.</flux:callout.text>
    </flux:callout>
@else
    {{-- The jobs this department books over and over. Grouped by
         service type so the heading can tick a whole standard
         service at once. --}}
    {{-- Children bind straight into the array with wire:model — the
         DOM owns a checkbox's `checked`, so deriving it from the
         server with :checked + wire:click desyncs the moment two
         clicks land close together. Group headings and the count
         are computed in Alpine off $wire, so they stay instant and
         never disagree with the boxes underneath them. --}}
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden"
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
            get total() {
                return this.ids().length
                    + ($wire.requestedRepairIds ?? []).length
                    + ($wire.manualRepairs ?? []).length
            },
        }">
        <div class="flex items-center justify-between gap-3 px-3 py-2 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm">Job Description ({{ $this->departmentName }})</flux:heading>
            <flux:badge size="sm" color="lime" x-show="total > 0" x-cloak>
                <span x-text="total"></span>&nbsp;selected
            </flux:badge>
            <flux:badge size="sm" color="zinc" x-show="total === 0">None selected</flux:badge>
        </div>

        @forelse ($this->frequentServiceGroups as $group => $jobs)
            <div class="px-3 py-2.5 border-b border-zinc-100 dark:border-zinc-800 last:border-b-0"
                wire:key="grp-{{ Str::slug($group) }}"
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
        @empty
            <div class="px-3 py-3">
                <flux:text size="sm" class="text-zinc-500">No frequent jobs set up for this department yet.</flux:text>
            </div>
        @endforelse
    </div>

    {{-- Anything not common enough to earn a checkbox: pick it from
         the master, or type it when the master has not caught up. --}}
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-3 py-2 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm">Requested Repairs (other than above)</flux:heading>
        </div>
        <div class="p-3 space-y-3">
            @if ($this->otherJobDescriptions->isNotEmpty())
                <flux:select
                    wire:model.live="requestedRepairIds"
                    variant="listbox"
                    multiple
                    searchable
                    clearable
                    placeholder="Search this department's other jobs…"
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

    <flux:separator variant="subtle" />
@endif
