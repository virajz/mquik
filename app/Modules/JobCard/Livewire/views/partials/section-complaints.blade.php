<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
    <div>
        <flux:heading size="lg">Customer Complaints</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">What the customer reported. One row per distinct complaint.</flux:text>
    </div>
    <div class="space-y-3 min-w-0">
        {{-- Moved here from Timing & Routing: a package and a job description
             describe the work, which is what this section is about. --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model="service_package_id" variant="listbox" searchable clearable label="Service Package" placeholder="None / Combo / AMC">
                @foreach ($this->servicePackages as $sp)
                    <flux:select.option :value="$sp->id" wire:key="sp-{{ $sp->id }}">{{ $sp->name }}{{ $sp->is_amc ? ' (AMC)' : '' }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="job_description_id" variant="listbox" searchable clearable label="Job Type / Description" placeholder="Standard job description…">
                @foreach ($this->jobDescriptions as $jd)
                    <flux:select.option :value="$jd->id" wire:key="jd-{{ $jd->id }}">{{ $jd->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        {{-- The same shape as dept-wise quick services: tick what applies from
             the frequent list, grouped by category. Picking two complaints was
             two dropdowns and two cards before; now it is two clicks. --}}
        @if ($this->complaintGroups->isEmpty() && $this->otherComplaints->isEmpty())
            <flux:callout variant="secondary" icon="information-circle" inline>
                <flux:callout.text>
                    {{ $workshop_department_id ? 'No complaints on file for this department yet.' : 'Pick a department to see its complaints.' }}
                </flux:callout.text>
            </flux:callout>
        @else
            @if ($this->complaintGroups->isNotEmpty())
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="flex items-center justify-between gap-3 px-3 py-2 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm">Frequent complaints</flux:heading>
                    <div class="flex items-center gap-1">
                        @if (count($this->selectedComplaintIds))
                            <flux:badge size="sm" color="lime">{{ count($this->selectedComplaintIds) }}&nbsp;selected</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc">None selected</flux:badge>
                        @endif
                    </div>
                </div>

                @foreach ($this->complaintGroups as $group => $options)
                    @php($groupIds = $options->pluck('id')->map(fn ($id) => (string) $id)->all())
                    @php($selectedInGroup = count(array_intersect($groupIds, $this->selectedComplaintIds)))
                    <div class="px-3 py-2.5 border-b border-zinc-100 dark:border-zinc-800 last:border-b-0"
                        wire:key="cmp-grp-{{ Str::slug($group) }}">
                        <flux:checkbox
                            :label="$group"
                            class="font-medium"
                            :checked="$selectedInGroup === count($groupIds)"
                            :indeterminate="$selectedInGroup > 0 && $selectedInGroup < count($groupIds)"
                            wire:click="toggleComplaintGroup('{{ $group }}')"
                        />
                        <div class="mt-1.5 pl-6 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-x-4 gap-y-1">
                            @foreach ($options as $opt)
                                <flux:checkbox
                                    wire:key="cmp-{{ $opt->id }}"
                                    :label="$opt->name"
                                    :checked="in_array((string) $opt->id, $this->selectedComplaintIds, true)"
                                    wire:click="toggleComplaint({{ $opt->id }})"
                                />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @endif

            {{-- Anything not common enough to earn a checkbox: pick it here.
                 Same shape as "Requested Repairs (other than above)" on the
                 appointment screen. --}}
            @if ($this->otherComplaints->isNotEmpty())
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="flex items-center justify-between gap-2 px-3 py-2 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700">
                        <flux:heading size="sm">Complaints (other than above)</flux:heading>
                        <div class="flex items-center gap-1">
                            @can('requested_repair_master.create')
                                <flux:tooltip content="Complaint not on the list? Add it">
                                    <flux:button size="xs" icon="plus" variant="ghost" type="button" wire:click="openComplaintQuickAdd(0)" />
                                </flux:tooltip>
                            @endcan
                            @can('requested_repair_master.view')
                                <flux:tooltip content="Open Requested Repairs in a new tab">
                                    <flux:button size="xs" icon="arrow-top-right-on-square" variant="ghost" type="button"
                                        :href="route('requested-repair-master.index')" target="_blank" />
                                </flux:tooltip>
                            @endcan
                        </div>
                    </div>
                    <div class="p-3">
                        <flux:select wire:model.live="otherComplaintIds" variant="listbox" multiple searchable size="sm"
                            placeholder="Search and pick a complaint…">
                            @foreach ($this->otherComplaints as $opt)
                                <flux:select.option :value="(string) $opt->id" wire:key="oc-{{ $opt->id }}">{{ $opt->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
            @endif

            {{-- What ended up on the card, with the two things a checkbox has no
                 room for: whether it is a repeat, and when it was reported. --}}
            @if (count($complaints))
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($complaints as $i => $row)
                        @php($picked = $this->complaintOptions->firstWhere('id', (int) ($row['requested_repair_id'] ?? 0)))
                        <div wire:key="complaint-row-{{ $i }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-3 py-2 text-sm">
                            <span class="font-medium min-w-0 flex-1 truncate">{{ $picked?->name ?? ($row['description'] ?: 'Unnamed complaint') }}</span>

                            <flux:tooltip content="Same complaint as a previous visit">
                                <flux:checkbox wire:model="complaints.{{ $i }}.is_repeat_job" label="Repeat" />
                            </flux:tooltip>

                            <span class="text-xs text-zinc-500 tabular-nums whitespace-nowrap">
                                {{ ! empty($row['reported_at'])
                                    ? \Illuminate\Support\Carbon::parse($row['reported_at'])->format('d/m/Y h:i A')
                                    : 'stamped on save' }}
                            </span>

                            <flux:button type="button" size="xs" variant="ghost" icon="x-mark"
                                wire:click="removeComplaint({{ $i }})" />
                        </div>
                    @endforeach
                </div>
            @endif
        @endif

    </div>
</section>
