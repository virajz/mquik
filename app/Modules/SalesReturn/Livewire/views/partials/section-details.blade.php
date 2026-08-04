@use(App\Modules\SalesReturn\Models\SalesReturn)
<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
    <div>
        <flux:heading size="lg">Return Details</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Return type, the source invoice, customer, reason and staff.</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model.live="return_type" variant="listbox" label="Return Type" required>
                @foreach (SalesReturn::returnTypes() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="reference_mode" variant="listbox" label="Invoice Reference" required>
                @foreach (SalesReturn::referenceModes() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
            @if ($return_type === 'counter')
                <flux:select wire:model="counter_sales_invoice_id" variant="listbox" searchable clearable label="Counter Invoice" placeholder="Link source…">
                    @foreach ($this->counterInvoices as $ci)
                        <flux:select.option :value="$ci->id" wire:key="ci-{{ $ci->id }}">{{ $ci->invoice_no }}</flux:select.option>
                    @endforeach
                </flux:select>
            @else
                <flux:select wire:model="regular_sales_invoice_id" variant="listbox" searchable clearable label="Sales Invoice" placeholder="Link source…">
                    @foreach ($this->regularInvoices as $ri)
                        <flux:select.option :value="$ri->id" wire:key="ri-{{ $ri->id }}">{{ $ri->invoice_no }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model.live="customer_id" variant="listbox" searchable label="Customer" placeholder="Search name or mobile…" required :filter="false">
                <x-slot name="search">
                    <flux:select.search wire:model.live.debounce.250ms="customerSearch" placeholder="Name or phone…" />
                </x-slot>
                @foreach ($this->customers as $c)
                    <flux:select.option :value="$c['id']" wire:key="cust-{{ $c['id'] }}">{{ $c['label'] }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="customer_vehicle_id" variant="listbox" searchable clearable label="Customer Vehicle" placeholder="If applicable…">
                @foreach ($this->vehiclePickerOptions as $v)
                    <flux:select.option :value="$v['id']" wire:key="cv-{{ $v['id'] }}">{{ $v['label'] }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <flux:error name="customer_id" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model="sales_return_reason_id" variant="listbox" searchable clearable label="Sales Return Reason" placeholder="Why returned…">
                @foreach ($this->returnReasons as $rr)
                    <flux:select.option :value="$rr->id" wire:key="srr-{{ $rr->id }}">{{ $rr->name }}</flux:select.option>
                @endforeach
            </flux:select>
            @if ($return_type === 'insurance')
                <flux:select wire:model="insurance_company_id" variant="listbox" searchable clearable label="Insurance Company" placeholder="For insurance…">
                    @foreach ($this->insuranceCompanies as $ic)
                        <flux:select.option :value="$ic->id" wire:key="ic-{{ $ic->id }}">{{ $ic->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="service_package_id" variant="listbox" searchable clearable label="Service Package" placeholder="Optional…">
                @foreach ($this->servicePackages as $sp)
                    <flux:select.option :value="$sp->id" wire:key="sp-{{ $sp->id }}">{{ $sp->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="amc_package_id" variant="listbox" searchable clearable label="AMC Package" placeholder="Optional…">
                @foreach ($this->amcPackages as $ap)
                    <flux:select.option :value="$ap->id" wire:key="amc-{{ $ap->id }}">{{ $ap->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="department_id" variant="listbox" searchable clearable label="Department" placeholder="Optional…">
                @foreach ($this->departments as $d)
                    <flux:select.option :value="$d->id" wire:key="dep-{{ $d->id }}">{{ $d->name }}</flux:select.option>
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
            <flux:select wire:model="vendor_id" variant="listbox" searchable clearable label="Vendor" placeholder="Supplier…" :filter="false">
            <x-slot name="search">
                <flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Type a vendor name or code…" />
            </x-slot>
                @foreach ($this->vendors as $v)
                    <flux:select.option :value="$v->id" wire:key="ven-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>
</section>
