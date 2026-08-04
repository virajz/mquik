<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
    <div>
        <flux:heading size="lg">Entry Details</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Vendor, job card, invoice, transport and loss reason.</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model="vendor_id" variant="listbox" searchable label="Vendor" placeholder="Pick a vendor…" required :filter="false">
            <x-slot name="search">
                <flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Type a vendor name or code…" />
            </x-slot>
                @foreach ($this->vendors as $v)
                    <flux:select.option :value="$v->id" wire:key="ven-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="vendor_type_id" variant="listbox" searchable clearable label="Vendor Type" placeholder="Service Provider / Contractor…">
                @foreach ($this->vendorTypes as $vt)
                    <flux:select.option :value="$vt->id" wire:key="vt-{{ $vt->id }}">{{ $vt->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model.live="job_card_id" variant="listbox" searchable clearable label="Job Card" placeholder="Link a job card…">
                @foreach ($this->jobCards as $jc)
                    <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="outside_work_order_ref" label="Outside Work Order Ref" placeholder="Optional reference" class:input="font-mono uppercase" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="department_id" variant="listbox" searchable clearable label="Department" placeholder="Optional…">
                @foreach ($this->departments as $d)
                    <flux:select.option :value="$d->id" wire:key="dep-{{ $d->id }}">{{ $d->name }}</flux:select.option>
                @endforeach
            </flux:select>
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

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:input wire:model="invoice_no" label="Invoice No." placeholder="Vendor invoice" class:input="font-mono uppercase" />
            <flux:date-picker wire:model="invoice_date" label="Invoice Date" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
            <flux:select wire:model="transport_mode_id" variant="listbox" searchable clearable label="Transport Mode" placeholder="Courier / Porter…">
                @foreach ($this->transportModes as $tm)
                    <flux:select.option :value="$tm->id" wire:key="tm-{{ $tm->id }}">{{ $tm->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="loss_reason_id" variant="listbox" searchable clearable label="Loss Reason" placeholder="Damaged / Defective…">
                @foreach ($this->lossReasons as $lr)
                    <flux:select.option :value="$lr->id" wire:key="lr-{{ $lr->id }}">{{ $lr->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>
</section>
