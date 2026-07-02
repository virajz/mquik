<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
    <div>
        <flux:heading size="lg">Payment Details</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Vendor, the purchase bill it settles, amount and who paid.</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:select wire:model="vendor_id" variant="listbox" searchable label="Vendor" placeholder="Search vendor…" required class="lg:col-span-2">
                @foreach ($this->vendors as $v)
                    <flux:select.option :value="$v->id" wire:key="ven-{{ $v->id }}">{{ $v->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input type="number" step="0.01" min="0" wire:model="amount" label="Amount" required />
            <flux:select wire:model="paid_by_id" variant="listbox" searchable clearable label="Paid By" placeholder="—">
                @foreach ($this->employees as $e)
                    <flux:select.option :value="$e->id" wire:key="pb-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <flux:error name="vendor_id" />

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:select wire:model="purchase_entry_id" variant="listbox" searchable clearable label="Against Purchase Invoice" placeholder="Optional…">
                @foreach ($this->purchaseEntries as $pe)
                    <flux:select.option :value="$pe->id" wire:key="pe-{{ $pe->id }}">{{ $pe->purchase_no }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="advance_payment_id" variant="listbox" searchable clearable label="Advance Payment Ref" placeholder="If adjusting advance…">
                @foreach ($this->advancePayments as $ap)
                    <flux:select.option :value="$ap->id" wire:key="ap-{{ $ap->id }}">{{ $ap->payment_no }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="payment_mode_id" variant="listbox" searchable clearable label="Payment Mode" placeholder="—">
                @foreach ($this->paymentModes as $pm)
                    <flux:select.option :value="$pm->id" wire:key="pm-{{ $pm->id }}">{{ $pm->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="bank_id" variant="listbox" searchable clearable label="Bank" placeholder="—">
                @foreach ($this->banks as $b)
                    <flux:select.option :value="$b->id" wire:key="bk-{{ $b->id }}">{{ $b->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:input wire:model="reference_no" label="Reference / UTR No." placeholder="Transaction / UTR / bill reference" class:input="font-mono uppercase" />
    </div>
</section>
