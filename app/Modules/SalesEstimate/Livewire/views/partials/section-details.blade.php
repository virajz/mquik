@use(App\Modules\SalesEstimate\Models\SalesEstimate)
<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
    <div>
        <flux:heading size="lg">Estimate Details</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Customer, vehicle, what the estimate is for, and who's handling it.</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        <flux:select wire:model.live="customer_vehicle_id" variant="listbox" searchable label="Customer & Vehicle" placeholder="Search reg no or customer…" required>
            @foreach ($this->vehiclePickerOptions as $v)
                <flux:select.option :value="$v['id']" wire:key="cv-{{ $v['id'] }}">{{ $v['label'] }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:error name="customer_vehicle_id" />
        <flux:error name="customer_id" />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="estimate_type" variant="listbox" label="Estimate Type" required>
                @foreach (SalesEstimate::estimateTypes() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="parts_category" variant="listbox" label="Parts Category" required>
                @foreach (SalesEstimate::partsCategories() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="damage_cause_id" variant="listbox" searchable clearable label="Damage Cause" placeholder="Optional…">
                @foreach ($this->damageCauses as $d)
                    <flux:select.option :value="$d->id" wire:key="dc-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model.live="job_card_id" variant="listbox" searchable clearable label="Job Card" placeholder="Link a job card…">
                @foreach ($this->jobCards as $jc)
                    <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="department_id" variant="listbox" searchable clearable label="Department" placeholder="Optional…">
                @foreach ($this->departments as $d)
                    <flux:select.option :value="$d->id" wire:key="dep-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="service_type_id" variant="listbox" searchable clearable label="Service Type" placeholder="Optional…">
                @foreach ($this->serviceTypes as $s)
                    <flux:select.option :value="$s->id" wire:key="st-{{ $s->id }}">{{ $s->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model="advisor_id" variant="listbox" searchable clearable label="Advisor" placeholder="Optional…">
                @foreach ($this->employees as $e)
                    <flux:select.option :value="$e->id" wire:key="adv-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="technician_id" variant="listbox" searchable clearable label="Technician" placeholder="Optional…">
                @foreach ($this->employees as $e)
                    <flux:select.option :value="$e->id" wire:key="tec-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="insurance_company_id" variant="listbox" searchable clearable label="Insurance Company" placeholder="For insurance estimates…">
                @foreach ($this->insuranceCompanies as $ic)
                    <flux:select.option :value="$ic->id" wire:key="ic-{{ $ic->id }}">{{ $ic->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="policy_no" label="Policy No." placeholder="Optional" class:input="font-mono uppercase" />
            <flux:select wire:model="service_package_id" variant="listbox" searchable clearable label="Service Package" placeholder="Optional…">
                @foreach ($this->servicePackages as $sp)
                    <flux:select.option :value="$sp->id" wire:key="sp-{{ $sp->id }}">{{ $sp->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>
</section>
