@use(App\Modules\Consumable\Models\Consumable)
<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
    <div>
        <flux:heading size="lg">Consumable Details</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Category, references, loss classification, approval and who logged it.</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="consumable_category_id" variant="listbox" searchable clearable label="Consumable Category" placeholder="Paint / Workshop / Washing…">
                @foreach ($this->categories as $c)
                    <flux:select.option :value="$c->id" wire:key="cat-{{ $c->id }}">{{ $c->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="job_card_id" variant="listbox" searchable clearable label="Job Card" placeholder="Link…">
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model="challan_id" variant="listbox" searchable clearable label="Purchase Ref (Challan)" placeholder="Optional…">
                @foreach ($this->challans as $c)
                    <flux:select.option :value="$c->id" wire:key="ch-{{ $c->id }}">{{ $c->challan_no }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="purchase_entry_id" variant="listbox" searchable clearable label="Purchase Ref (Direct)" placeholder="Optional…">
                @foreach ($this->purchaseEntries as $p)
                    <flux:select.option :value="$p->id" wire:key="pe-{{ $p->id }}">{{ $p->purchase_no }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="loss_type_id" variant="listbox" searchable clearable label="Loss Type" placeholder="Warranty / Damaged…">
                @foreach ($this->lossTypes as $lt)
                    <flux:select.option :value="$lt->id" wire:key="lt-{{ $lt->id }}">{{ $lt->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="loss_reason_id" variant="listbox" searchable clearable label="Loss Reason" placeholder="Evaporation / Spillage…">
                @foreach ($this->lossReasons as $lr)
                    <flux:select.option :value="$lr->id" wire:key="lr-{{ $lr->id }}">{{ $lr->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="vendor_id" variant="listbox" searchable clearable label="Vendor" placeholder="Contractor / outsource…">
                @foreach ($this->vendors as $v)
                    <flux:select.option :value="$v->id" wire:key="ven-{{ $v->id }}">{{ $v->name }}</flux:select.option>
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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="approval_authority" variant="listbox" clearable label="Approval Authority" placeholder="—">
                @foreach (Consumable::approvalAuthorities() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="approval_status" variant="listbox" label="Approval Status" required>
                @foreach (Consumable::approvalStatuses() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="communication_mode" variant="listbox" clearable label="Communication Mode" placeholder="—">
                @foreach (Consumable::communicationModes() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Optional notes on the consumption / loss." />
    </div>
</section>
