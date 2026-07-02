@use(App\Modules\ReceiptRefund\Models\ReceiptRefund)
<section class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-10">
    <div>
        <flux:heading size="lg">Refund Details</flux:heading>
        <flux:text size="sm" class="mt-1 text-zinc-500">What the refund is against, the customer, amount and the documents it references.</flux:text>
    </div>
    <div class="space-y-4 min-w-0">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="refund_against" variant="listbox" label="Refund Against" required>
                @foreach (ReceiptRefund::refundAgainstOptions() as $k => $l)
                    <flux:select.option :value="$k">{{ $l }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="refund_type_id" variant="listbox" searchable clearable label="Refund Type" placeholder="Excess / duplicate / return…">
                @foreach ($this->refundTypes as $rt)
                    <flux:select.option :value="$rt->id" wire:key="rt-{{ $rt->id }}">{{ $rt->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input type="number" step="0.01" min="0" wire:model="amount" label="Refund Amount" required />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model.live="customer_id" variant="listbox" searchable label="Customer" placeholder="Search name or mobile…" required>
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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model="insurance_company_id" variant="listbox" searchable clearable label="Insurance Company" placeholder="For insurance…">
                @foreach ($this->insuranceCompanies as $ic)
                    <flux:select.option :value="$ic->id" wire:key="ic-{{ $ic->id }}">{{ $ic->name }}</flux:select.option>
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

        <div>
            <flux:text size="sm" class="font-medium text-zinc-600 dark:text-zinc-400 mb-2">Reference Documents</flux:text>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:select wire:model="advance_receipt_id" variant="listbox" searchable clearable label="Advance Receipt" placeholder="—">
                    @foreach ($this->receipts as $r)
                        <flux:select.option :value="$r->id" wire:key="ar-{{ $r->id }}">{{ $r->receipt_no }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="regular_receipt_id" variant="listbox" searchable clearable label="Regular Receipt" placeholder="—">
                    @foreach ($this->receipts as $r)
                        <flux:select.option :value="$r->id" wire:key="rr-{{ $r->id }}">{{ $r->receipt_no }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="job_card_id" variant="listbox" searchable clearable label="Job Card" placeholder="—">
                    @foreach ($this->jobCards as $jc)
                        <flux:select.option :value="$jc->id" wire:key="jc-{{ $jc->id }}">{{ $jc->job_card_no }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="sales_estimate_id" variant="listbox" searchable clearable label="Estimate" placeholder="—">
                    @foreach ($this->estimates as $e)
                        <flux:select.option :value="$e->id" wire:key="se-{{ $e->id }}">{{ $e->estimate_no }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="regular_sales_invoice_id" variant="listbox" searchable clearable label="Invoice" placeholder="—">
                    @foreach ($this->regularInvoices as $inv)
                        <flux:select.option :value="$inv->id" wire:key="inv-{{ $inv->id }}">{{ $inv->invoice_no }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="sales_return_id" variant="listbox" searchable clearable label="Credit Note (Sales Return)" placeholder="—">
                    @foreach ($this->salesReturns as $sr)
                        <flux:select.option :value="$sr->id" wire:key="cn-{{ $sr->id }}">{{ $sr->return_no }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select wire:model="advisor_id" variant="listbox" searchable clearable label="Service Advisor" placeholder="—">
                @foreach ($this->employees as $emp)
                    <flux:select.option :value="$emp->id" wire:key="adv-{{ $emp->id }}">{{ $emp->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="refunded_by_id" variant="listbox" searchable clearable label="Refund By" placeholder="—">
                @foreach ($this->employees as $emp)
                    <flux:select.option :value="$emp->id" wire:key="rby-{{ $emp->id }}">{{ $emp->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>
</section>
