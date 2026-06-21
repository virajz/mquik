@php($lean = $lean ?? false)
<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10 py-6">
    <div>
        <flux:heading size="lg">Timing & Routing</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">When the vehicle arrived, when it's promised back, and who owns it.</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:date-picker wire:model="opened_date" label="Opened Date" placeholder="Today" with-today selectable-header fixed-weeks type="input" />
            <flux:time-picker wire:model="opened_time" label="Opened Time" placeholder="Now" type="input" />
            <flux:date-picker wire:model="promised_date" label="Promised Date" placeholder="Tomorrow" with-today selectable-header fixed-weeks type="input" />
            <flux:time-picker wire:model="promised_time" label="Promised Time" type="input" />
            @unless ($lean)
                <flux:date-picker wire:model="expected_completion_date" label="Expected Completion Date" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
                <flux:time-picker wire:model="expected_completion_time" label="Expected Completion Time" type="input" />
            @endunless
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="workshop_department_id" variant="listbox" searchable label="Department" placeholder="Pick a department…" required>
                @foreach ($this->workshopDepartments as $d)
                    <flux:select.option :value="$d->id" wire:key="wd-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="assigned_advisor_id" variant="listbox" searchable label="Advisor" placeholder="Pick an advisor…" required>
                @foreach ($this->employees as $e)
                    <flux:select.option :value="$e->id" wire:key="adv-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="assigned_technician_id" variant="listbox" searchable clearable label="Technician" placeholder="Auto-assign later or pick now…">
                @foreach ($this->employees as $e)
                    <flux:select.option :value="$e->id" wire:key="tech-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model="service_type_id" variant="listbox" searchable clearable label="Service Type" placeholder="Pick a service type…">
                @foreach ($this->serviceTypes as $st)
                    <flux:select.option :value="$st->id" wire:key="st-{{ $st->id }}">{{ $st->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="service_package_id" variant="listbox" searchable clearable label="Service Package" placeholder="None / Combo / AMC">
                @foreach ($this->servicePackages as $sp)
                    <flux:select.option :value="$sp->id" wire:key="sp-{{ $sp->id }}">{{ $sp->name }}{{ $sp->is_amc ? ' (AMC)' : '' }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @unless ($lean)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:select wire:model="status" variant="listbox" label="Status" required>
                    @foreach (\App\Modules\JobCard\Models\JobCard::statuses() as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="current_stage_id" variant="listbox" searchable clearable label="Stage" placeholder="Lifecycle stage…">
                    @foreach ($this->jobStages as $stage)
                        <flux:select.option :value="$stage->id" wire:key="stg-{{ $stage->id }}">{{ $stage->name }} ({{ ucfirst($stage->track) }})</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="pending_reason_id" variant="listbox" searchable clearable label="Pending Reason" placeholder="If on hold…">
                    @foreach ($this->pendingReasons as $pr)
                        <flux:select.option :value="$pr->id" wire:key="pr-{{ $pr->id }}">{{ $pr->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @endunless
    </div>
</section>
