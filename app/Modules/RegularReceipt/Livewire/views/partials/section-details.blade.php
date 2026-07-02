@use(App\Modules\RegularReceipt\Models\RegularReceipt)
<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
    <div>
        <flux:heading size="lg">Payment Details</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">Customer, the bill it settles, amount and how it was paid.</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model.live="customer_id" variant="listbox" searchable label="Customer" placeholder="Search name or mobile…" required>
                @foreach ($this->customers as $c)
                    <flux:select.option :value="$c['id']" wire:key="cust-{{ $c['id'] }}">{{ $c['label'] }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="insurance_company_id" variant="listbox" searchable clearable label="Insurance Company" placeholder="For insurance payment…">
                @foreach ($this->insuranceCompanies as $ic)
                    <flux:select.option :value="$ic->id" wire:key="ic-{{ $ic->id }}">{{ $ic->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <flux:error name="customer_id" />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="regular_sales_invoice_id" variant="listbox" searchable clearable label="Against Sales Invoice" placeholder="Optional…">
                @foreach ($this->regularInvoices as $ri)
                    <flux:select.option :value="$ri->id" wire:key="ri-{{ $ri->id }}">{{ $ri->invoice_no }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="counter_sales_invoice_id" variant="listbox" searchable clearable label="Against Counter Invoice" placeholder="Optional…">
                @foreach ($this->counterInvoices as $ci)
                    <flux:select.option :value="$ci->id" wire:key="ci-{{ $ci->id }}">{{ $ci->invoice_no }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="advance_receipt_id" variant="listbox" searchable clearable label="Advance Receipt Ref" placeholder="If adjusting advance…">
                @foreach ($this->advanceReceipts as $ar)
                    <flux:select.option :value="$ar->id" wire:key="ar-{{ $ar->id }}">{{ $ar->receipt_no }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:input type="number" step="0.01" min="0" wire:model="amount" label="Amount" required />
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
            <flux:select wire:model="received_by_id" variant="listbox" searchable clearable label="Received By" placeholder="—">
                @foreach ($this->employees as $e)
                    <flux:select.option :value="$e->id" wire:key="rb-{{ $e->id }}">{{ $e->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:input wire:model="reference_no" label="Reference / UTR No." placeholder="Transaction / UTR / bill reference" class:input="font-mono uppercase" />
    </div>
</section>
