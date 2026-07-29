@use(App\Modules\FinalWorkOrder\Models\FinalWorkOrder)
<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
    <div>
        <flux:heading size="lg">Work Order Setup</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Job card, department, who works it, the bay, and the inspection template.</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        <flux:select wire:model="job_card_id" variant="listbox" searchable label="Job Card" placeholder="Pick a job card…" required>
            @foreach ($this->jobCards as $jc)
                <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">
                    {{ $jc->job_card_no }} — {{ trim($jc->customer?->first_name.' '.($jc->customer?->last_name ?? '')) }}
                    @if ($jc->customerVehicle) · {{ $jc->customerVehicle->registration_no }} @endif
                </flux:select.option>
            @endforeach
        </flux:select>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model="department_id" variant="listbox" searchable clearable label="Department" placeholder="Optional…">
                @foreach ($this->departments as $d)
                    <flux:select.option :value="$d->id" wire:key="dept-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="service_type_id" variant="listbox" searchable clearable label="Service Type" placeholder="Optional…">
                @foreach ($this->serviceTypes as $s)
                    <flux:select.option :value="$s->id" wire:key="st-{{ $s->id }}">{{ $s->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model="advisor_id" variant="listbox" searchable clearable label="Advisor" placeholder="Pick an advisor…">
                @foreach ($this->employees as $e)
                    <flux:select.option :value="$e->id" wire:key="adv-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="technician_id" variant="listbox" searchable clearable label="Technician" placeholder="Pick a technician…">
                @foreach ($this->employees as $e)
                    <flux:select.option :value="$e->id" wire:key="tech-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="bay_id" variant="listbox" searchable clearable label="Bay" placeholder="Pick a bay…">
                @foreach ($this->bays as $b)
                    <flux:select.option :value="$b->id" wire:key="bay-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="inspection_template_id" variant="listbox" searchable clearable label="Inspection Template" placeholder="Optional…">
                @foreach ($this->templates as $t)
                    <flux:select.option :value="$t->id" wire:key="tpl-{{ $t->id }}">{{ $t->name }} ({{ strtoupper($t->applies_to) }})</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="priority_id" variant="listbox" label="Priority" placeholder="Normal">
                @foreach ($this->priorities as $p)
                    <flux:select.option :value="$p->id" wire:key="prio-{{ $p->id }}">{{ $p->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @if ($lean)
            <flux:text size="xs" class="text-zinc-500">Picking a template now snapshots its checkpoints into the checklist.</flux:text>
        @endif
    </div>
</section>
