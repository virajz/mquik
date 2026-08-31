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

        @if (count($complaints) === 0)
            <div class="rounded-md border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center text-sm text-zinc-500">
                No complaints captured yet.
                <div class="mt-2">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addComplaint">Add complaint</flux:button>
                </div>
            </div>
        @else
            @foreach ($complaints as $i => $row)
                <div wire:key="complaint-row-{{ $i }}" class="p-3 rounded-md border border-zinc-200 dark:border-zinc-800 space-y-2">
                    <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_180px_40px] gap-2 items-end">
                        {{-- Complaints come from the Requested Repair master: that is
                             the list a customer's words map onto. Standard
                             observations belong to checklist templates. --}}
                        <flux:select
                            wire:model.live="complaints.{{ $i }}.requested_repair_id"
                            variant="listbox"
                            searchable
                            size="sm"
                            label="Complaint"
                            placeholder="Pick a complaint…"
                            required
                        >
                            @foreach ($this->complaintOptions as $opt)
                                <flux:select.option :value="$opt->id" wire:key="cmp-{{ $i }}-{{ $opt->id }}">{{ $opt->name }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        @can('requested_repair_master.create')
                            <flux:tooltip content="Complaint not on the list? Add it">
                                <flux:button type="button" size="sm" variant="ghost" icon="plus"
                                    wire:click="openComplaintQuickAdd({{ $i }})" class="h-9!" />
                            </flux:tooltip>
                        @endcan

                        {{-- The group follows the complaint — never set by hand. --}}
                        <flux:field>
                            <flux:label>Category</flux:label>
                            <div class="flex items-center h-9">
                                @php
                                    $picked = $this->complaintOptions->firstWhere('id', (int) ($row['requested_repair_id'] ?? 0));
                                    $group = $picked?->complaintType?->name;
                                @endphp
                                @if ($group)
                                    <flux:badge size="sm" color="zinc">{{ $group }}</flux:badge>
                                @else
                                    <span class="text-sm text-zinc-400">—</span>
                                @endif
                            </div>
                        </flux:field>

                        <flux:button type="button" size="sm" variant="ghost" icon="x-mark"
                            wire:click="removeComplaint({{ $i }})" class="h-9!" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-[220px_1fr] gap-2 items-end">
                        {{-- Each complaint carries its own moment, so an advisor and a
                             technician never argue about which one came when. --}}
                        <flux:input type="datetime-local" size="sm" label="Reported at"
                            wire:model="complaints.{{ $i }}.reported_at" />
                        <flux:checkbox wire:model="complaints.{{ $i }}.is_repeat_job"
                            label="Repeat job — same complaint as a previous visit" />
                    </div>

                    <flux:error name="complaints.{{ $i }}.requested_repair_id" />
                </div>
            @endforeach

            {{-- The button belongs after the last row: that is where the eye is
                 once the previous complaint has been filled in. --}}
            <div class="flex justify-start pt-1">
                <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addComplaint">Add complaint</flux:button>
            </div>
        @endif
    </div>
</section>
