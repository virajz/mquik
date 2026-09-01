@php($lean = $lean ?? false)
<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
    <div>
        <flux:heading size="lg">Timing & Routing</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">When the vehicle arrived, when it's promised back, and who owns it.</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Fixed: when the card was opened is a fact, stamped once. Only the
                 promise and the expected completion are the advisor's to move. --}}
            <flux:field>
                <flux:label>Opened Date</flux:label>
                <flux:input :value="\Illuminate\Support\Carbon::parse($opened_date)->format('d/m/Y')" readonly class:input="text-zinc-500" />
            </flux:field>
            <flux:field>
                <flux:label>Opened Time</flux:label>
                <flux:input :value="$opened_time" readonly class:input="text-zinc-500 font-mono" />
                <flux:description>Recorded automatically — cannot be changed.</flux:description>
            </flux:field>
            <flux:date-picker locale="en-IN" wire:model="promised_date" label="Promised Date" placeholder="Tomorrow" with-today selectable-header fixed-weeks type="input" />
            <flux:time-picker wire:model="promised_time" label="Promised Time" type="input" />
        </div>

        {{-- Routing runs in the order the desk works it: department, then what
             kind of job, then who owns it, then who does it. --}}
        @php($staff = $this->employeesByDepartment)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model.live="workshop_department_id" variant="listbox" searchable label="1. Department" placeholder="Pick a department…" required>
                @foreach ($this->workshopDepartments as $d)
                    <flux:select.option :value="$d->id" wire:key="wd-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="service_type_id" variant="listbox" searchable clearable label="2. Service Type"
                :placeholder="$workshop_department_id ? 'Pick a service type…' : 'Pick a department first'"
                :disabled="! $workshop_department_id">
                @foreach ($this->serviceTypesForDepartment as $st)
                    <flux:select.option :value="$st->id" wire:key="st-{{ $st->id }}">{{ $st->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="assigned_advisor_id" variant="listbox" searchable label="3. Advisor"
                :placeholder="$workshop_department_id ? 'Pick an advisor…' : 'Pick a department first'"
                :disabled="! $workshop_department_id" required>
                @foreach ($staff['advisors'] as $e)
                    <flux:select.option :value="$e->id" wire:key="adv-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div>
                <flux:select wire:model="assigned_technician_id" variant="listbox" searchable clearable label="4. Technician"
                    :placeholder="$workshop_department_id ? 'Auto-assign later or pick now…' : 'Pick a department first'"
                    :disabled="! $workshop_department_id">
                    @foreach ($staff['technicians'] as $e)
                        <flux:select.option :value="$e->id" wire:key="tech-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                @if ($technician_assigned_at)
                    <flux:text size="sm" class="mt-1 text-zinc-500"><flux:icon.clock class="inline size-3 -mt-0.5" /> Assigned {{ $technician_assigned_at }}</flux:text>
                @endif
            </div>
            @if ($workshop_department_id && $staff['advisors']->isEmpty())
                <div class="md:col-span-2">
                    <flux:text size="sm" class="text-amber-600 dark:text-amber-500">
                        No advisors are attached to this department yet — set their department on the Employees master.
                    </flux:text>
                </div>
            @endif
        </div>

        {{-- Status, stage and pending reason are job-history facts: they move as
             work happens and each change is already timestamped on the timeline,
             so they are shown here rather than typed. --}}
        @unless ($lean)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide text-zinc-400">Status</dt>
                            <dd class="mt-0.5">
                                <flux:badge size="sm" :color="match ($status) {
                                    'open' => 'amber', 'in_progress' => 'blue', 'awaiting_parts' => 'sky',
                                    'awaiting_approval' => 'purple', 'completed' => 'lime', 'closed' => 'zinc',
                                    'cancelled' => 'red', default => 'zinc',
                                }">{{ \App\Modules\JobCard\Models\JobCard::statuses()[$status] ?? $status }}</flux:badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide text-zinc-400">Stage</dt>
                            <dd class="mt-0.5 text-sm font-medium">{{ $this->currentStageName ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide text-zinc-400">Pending Reason</dt>
                            <dd class="mt-0.5 text-sm font-medium">
                                {{ $this->pendingReasonName ?? '—' }}
                                @if ($this->pendingSince)
                                    <span class="text-xs text-zinc-500">· since {{ $this->pendingSince }}</span>
                                @endif
                            </dd>
                        </div>
                    </div>
                    @if ($editingId)
                        <flux:button size="sm" variant="ghost" icon="clock"
                            :href="route('job-history.show', $editingId)" wire:navigate>History</flux:button>
                    @endif
                </div>
            </div>
        @endunless
    </div>
</section>
