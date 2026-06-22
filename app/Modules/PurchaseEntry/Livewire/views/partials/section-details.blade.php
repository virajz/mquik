@use(App\Modules\PurchaseEntry\Models\PurchaseEntry)
<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
    <div>
        <flux:heading size="lg">Purchase Details</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Vendor, invoice, challan reference, transport and who handled it.</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model="vendor_id" variant="listbox" searchable label="Vendor" placeholder="Pick a vendor…" required>
                @foreach ($this->vendors as $v)
                    <flux:select.option :value="$v->id" wire:key="ven-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="purchase_type" variant="listbox" label="Purchase Type" required>
                @foreach (PurchaseEntry::purchaseTypes() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="invoice_type" variant="listbox" label="Invoice Type" required>
                @foreach (PurchaseEntry::invoiceTypes() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="invoice_no" label="Invoice No." placeholder="Vendor invoice" class:input="font-mono uppercase" />
            <flux:date-picker wire:model="invoice_date" label="Invoice Date" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="challan_id" variant="listbox" searchable clearable label="Challan Reference" placeholder="Link a challan…">
                @foreach ($this->challans as $c)
                    <flux:select.option :value="$c->id" wire:key="ch-{{ $c->id }}">{{ $c->challan_no }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="po_reference" label="PO Reference" placeholder="Optional" class:input="font-mono uppercase" />
            <flux:select wire:model="inventory_status" variant="listbox" label="Inventory Status" required>
                @foreach (PurchaseEntry::inventoryStatuses() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="transport_mode_id" variant="listbox" searchable clearable label="Transport Mode" placeholder="Self / Courier…">
                @foreach ($this->transportModes as $tm)
                    <flux:select.option :value="$tm->id" wire:key="tm-{{ $tm->id }}">{{ $tm->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="transport_company_id" variant="listbox" searchable clearable label="Transport Company" placeholder="Optional…">
                @foreach ($this->transportCompanies as $tc)
                    <flux:select.option :value="$tc->id" wire:key="tc-{{ $tc->id }}">{{ $tc->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="challan_reason_id" variant="listbox" searchable clearable label="Challan Reason" placeholder="Warranty / Damage…">
                @foreach ($this->challanReasons as $cr)
                    <flux:select.option :value="$cr->id" wire:key="cr-{{ $cr->id }}">{{ $cr->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model="job_card_id" variant="listbox" searchable clearable label="Job Card" placeholder="Link a job card…">
                @foreach ($this->jobCards as $jc)
                    <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="department_id" variant="listbox" searchable clearable label="Department" placeholder="Optional…">
                @foreach ($this->departments as $d)
                    <flux:select.option :value="$d->id" wire:key="dep-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
            <flux:select wire:model="driver_id" variant="listbox" searchable clearable label="Driver" placeholder="—">
                @foreach ($this->employees as $e)
                    <flux:select.option :value="$e->id" wire:key="dr-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>
</section>
