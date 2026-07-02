@use(App\Modules\Proforma\Models\Proforma)
<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
    <div>
        <flux:heading size="lg">Proforma Details</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Customer, vehicle, references, loss classification and warranty.</flux:text>
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
            <flux:select wire:model="job_card_id" variant="listbox" searchable clearable label="Job Card" placeholder="Link…">
                @foreach ($this->jobCards as $jc)
                    <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="sales_estimate_id" variant="listbox" searchable clearable label="Estimate" placeholder="Link…">
                @foreach ($this->estimates as $e)
                    <flux:select.option :value="$e->id" wire:key="se-{{ $e->id }}">{{ $e->estimate_no }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="service_package_id" variant="listbox" searchable clearable label="Service Package" placeholder="Optional…">
                @foreach ($this->servicePackages as $sp)
                    <flux:select.option :value="$sp->id" wire:key="sp-{{ $sp->id }}">{{ $sp->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
            <flux:select wire:model="vendor_id" variant="listbox" searchable clearable label="Vendor" placeholder="Contractor / outsource…">
                @foreach ($this->vendors as $v)
                    <flux:select.option :value="$v->id" wire:key="ven-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model="insurance_company_id" variant="listbox" searchable clearable label="Insurance Company" placeholder="For insurance…">
                @foreach ($this->insuranceCompanies as $ic)
                    <flux:select.option :value="$ic->id" wire:key="ic-{{ $ic->id }}">{{ $ic->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="policy_no" label="Policy No." placeholder="Optional" class:input="font-mono uppercase" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:select wire:model="loss_type_id" variant="listbox" searchable clearable label="Loss Type" placeholder="Warranty / Damaged…">
                @foreach ($this->lossTypes as $lt)
                    <flux:select.option :value="$lt->id" wire:key="lt-{{ $lt->id }}">{{ $lt->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="loss_reason_id" variant="listbox" searchable clearable label="Loss Reason" placeholder="Damaged / Leakage…">
                @foreach ($this->lossReasons as $lr)
                    <flux:select.option :value="$lr->id" wire:key="lr-{{ $lr->id }}">{{ $lr->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="warranty_type" variant="listbox" clearable label="Warranty Type" placeholder="—">
                @foreach (Proforma::warrantyTypes() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="warranty_period" variant="listbox" clearable label="Warranty Period" placeholder="—">
                @foreach (Proforma::warrantyPeriods() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="advisor_id" variant="listbox" searchable clearable label="Advisor" placeholder="—">
                @foreach ($this->employees as $e)
                    <flux:select.option :value="$e->id" wire:key="adv-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="technician_id" variant="listbox" searchable clearable label="Technician" placeholder="—">
                @foreach ($this->employees as $e)
                    <flux:select.option :value="$e->id" wire:key="tec-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="store_incharge_id" variant="listbox" searchable clearable label="Store In-charge" placeholder="—">
                @foreach ($this->employees as $e)
                    <flux:select.option :value="$e->id" wire:key="si-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:textarea wire:model="recommended_service" label="Recommended Service" rows="2" placeholder="Recommended additional work for the customer." />
    </div>
</section>
