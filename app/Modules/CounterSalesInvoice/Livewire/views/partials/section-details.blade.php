@use(App\Modules\CounterSalesInvoice\Models\CounterSalesInvoice)
<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
    <div>
        <flux:heading size="lg">Counter Sale Details</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Walk-in customer, delivery, warranty and staff for this over-the-counter parts sale.</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        <flux:select wire:model.live="customer_id" variant="listbox" searchable label="Customer" placeholder="Search name or mobile…" required>
            @foreach ($this->customers as $c)
                <flux:select.option :value="$c['id']" wire:key="cust-{{ $c['id'] }}">{{ $c['label'] }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:error name="customer_id" />

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:select wire:model="delivery_type" variant="listbox" label="Delivery Type" required>
                @foreach (CounterSalesInvoice::deliveryTypes() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="courier_company_id" variant="listbox" searchable clearable label="Courier Company" placeholder="If shipped…">
                @foreach ($this->courierCompanies as $cc)
                    <flux:select.option :value="$cc->id" wire:key="cc-{{ $cc->id }}">{{ $cc->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="transport_mode_id" variant="listbox" searchable clearable label="Transport Mode" placeholder="—">
                @foreach ($this->transportModes as $tm)
                    <flux:select.option :value="$tm->id" wire:key="tm-{{ $tm->id }}">{{ $tm->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="tracking_no" label="Tracking / Docket No." placeholder="Optional" class:input="font-mono uppercase" />
        </div>

        {{-- Delivery address reveals client-side (Alpine) for any non-counter delivery. --}}
        <div x-show="$wire.delivery_type !== 'counter_pickup'" x-cloak>
            <flux:textarea wire:model="delivery_address" label="Delivery Address" rows="2" placeholder="Where the parts are being delivered." />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:select wire:model="department_id" variant="listbox" searchable clearable label="Department" placeholder="Optional…">
                @foreach ($this->departments as $d)
                    <flux:select.option :value="$d->id" wire:key="dep-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="loss_type_id" variant="listbox" searchable clearable label="Loss Type" placeholder="Warranty / Damaged…">
                @foreach ($this->lossTypes as $lt)
                    <flux:select.option :value="$lt->id" wire:key="lt-{{ $lt->id }}">{{ $lt->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="warranty_type" variant="listbox" clearable label="Warranty Type" placeholder="—">
                @foreach (CounterSalesInvoice::warrantyTypes() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="warranty_period" variant="listbox" clearable label="Warranty Period" placeholder="—">
                @foreach (CounterSalesInvoice::warrantyPeriods() as $k => $l)
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
            <flux:select wire:model="vendor_id" variant="listbox" searchable clearable label="Vendor" placeholder="Supplier / outsource…" :filter="false">
            <x-slot name="search">
                <flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Type a vendor name or code…" />
            </x-slot>
                @foreach ($this->vendors as $v)
                    <flux:select.option :value="$v->id" wire:key="ven-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:textarea wire:model="recommended_service" label="Recommendation" rows="2" placeholder="Any recommendation for the customer." />
    </div>
</section>
