@use(App\Modules\GoodsReturn\Models\GoodsReturn)
<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
    <div>
        <flux:heading size="lg">Return Details</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Vendor, credit/debit note, references, transport and warranty.</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="vendor_id" variant="listbox" searchable label="Vendor" placeholder="Pick a vendor…" required :filter="false">
            <x-slot name="search">
                <flux:select.search wire:model.live.debounce.250ms="vendorSearch" placeholder="Type a vendor name or code…" />
            </x-slot>
                @foreach ($this->vendors as $v)
                    <flux:select.option :value="$v->id" wire:key="ven-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="document_type" variant="listbox" label="Document Type" required>
                @foreach (GoodsReturn::documentTypes() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="credit_note_type" variant="listbox" label="Credit Note Type" required>
                @foreach (GoodsReturn::creditNoteTypes() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="credit_note_reason_id" variant="listbox" searchable clearable label="Reason" placeholder="Poor Quality / Damage…">
                @foreach ($this->creditNoteReasons as $r)
                    <flux:select.option :value="$r->id" wire:key="cnr-{{ $r->id }}">{{ $r->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="purchase_entry_id" variant="listbox" searchable clearable label="Purchase Reference" placeholder="Link a purchase…">
                @foreach ($this->purchaseEntries as $p)
                    <flux:select.option :value="$p->id" wire:key="pe-{{ $p->id }}">{{ $p->purchase_no }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="challan_id" variant="listbox" searchable clearable label="Challan Reference" placeholder="Link a challan…">
                @foreach ($this->challans as $c)
                    <flux:select.option :value="$c->id" wire:key="ch-{{ $c->id }}">{{ $c->challan_no }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:input wire:model="grn_reference" label="Goods Return Note Ref" placeholder="GRN ref" class:input="font-mono uppercase" />
            <flux:date-picker wire:model="returned_at" label="Return Date" placeholder="Optional" with-today selectable-header fixed-weeks type="input" />
            <flux:select wire:model="transport_mode_id" variant="listbox" searchable clearable label="Transport Mode" placeholder="—">
                @foreach ($this->transportModes as $tm)
                    <flux:select.option :value="$tm->id" wire:key="tm-{{ $tm->id }}">{{ $tm->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="transport_company_id" variant="listbox" searchable clearable label="Transport Company" placeholder="—">
                @foreach ($this->transportCompanies as $tc)
                    <flux:select.option :value="$tc->id" wire:key="tc-{{ $tc->id }}">{{ $tc->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model="warranty_type" variant="listbox" clearable label="Warranty Type" placeholder="—">
                @foreach (GoodsReturn::warrantyTypes() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="warranty_period" variant="listbox" clearable label="Warranty Period" placeholder="—">
                @foreach (GoodsReturn::warrantyPeriods() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>
</section>
