<?php

namespace App\Modules\CounterSalesInvoice\Livewire;

use App\Modules\CounterSalesInvoice\Models\CounterSalesInvoice;
use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\Inventory\Models\StockEntry;
use App\Modules\InvoiceCancellationReasonMaster\Models\InvoiceCancellationReasonMaster;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\LossTypeMaster\Models\LossTypeMaster;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\TransportModeMaster\Models\TransportModeMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Counter Sales Invoice')]
class Edit extends Component
{
    public ?int $editingId = null;

    public ?string $invoice_no = null;

    public string $status = 'draft';

    public string $payment_status = 'unpaid';

    public string $delivery_type = 'counter_pickup';

    public ?int $customer_id = null;

    public ?int $courier_company_id = null;

    public ?int $transport_mode_id = null;

    public ?int $department_id = null;

    public ?int $loss_type_id = null;

    public ?int $payment_mode_id = null;

    public ?int $cancellation_reason_id = null;

    public ?int $advisor_id = null;

    public ?int $technician_id = null;

    public ?int $vendor_id = null;

    public ?string $warranty_type = null;

    public ?string $warranty_period = null;

    public ?string $discount_type = null;

    public ?string $tracking_no = null;

    public ?string $delivery_address = null;

    public ?string $recommended_service = null;

    public float $amount_paid = 0;

    public ?string $notes = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    /** @var list<array<string, mixed>> */
    public array $items = [];

    public function mount(?CounterSalesInvoice $counterSalesInvoice = null): void
    {
        if ($counterSalesInvoice && $counterSalesInvoice->exists) {
            $this->load($counterSalesInvoice);
        }
    }

    protected function load(CounterSalesInvoice $inv): void
    {
        $inv->load('items');

        $this->editingId = $inv->id;
        foreach ([
            'invoice_no', 'status', 'payment_status', 'delivery_type', 'customer_id', 'courier_company_id',
            'transport_mode_id', 'department_id', 'loss_type_id', 'payment_mode_id', 'cancellation_reason_id',
            'advisor_id', 'technician_id', 'vendor_id', 'warranty_type', 'warranty_period', 'discount_type',
            'tracking_no', 'delivery_address', 'recommended_service', 'notes',
        ] as $k) {
            $this->{$k} = $inv->{$k};
        }
        $this->amount_paid = (float) $inv->amount_paid;

        $this->items = $inv->items->map(fn ($i) => [
            'id' => $i->id,
            'line_type' => $i->line_type,
            'spare_id' => $i->spare_id,
            'labour_id' => $i->labour_id,
            'uom_id' => $i->uom_id,
            'tax_id' => $i->tax_id,
            'description' => $i->description,
            'hsn_code' => $i->hsn_code,
            'qty' => (float) $i->qty,
            'cost_rate' => (float) $i->cost_rate,
            'unit_rate' => (float) $i->unit_rate,
            'discount_value' => (float) $i->discount_value,
            'tax_percent' => (float) $i->tax_percent,
            'sequence_no' => (int) $i->sequence_no,
        ])->all();
    }

    public function updated(string $name, $value): void
    {
        if (preg_match('/^items\.(\d+)\.spare_id$/', $name, $m) && $value) {
            $this->prefill((int) $m[1], SpareMaster::with('tax')->find($value), 'hsn_code');
        }
        if (preg_match('/^items\.(\d+)\.labour_id$/', $name, $m) && $value) {
            $this->prefill((int) $m[1], LabourMaster::with('tax')->find($value), 'hsn_sac_code');
        }
    }

    protected function prefill(int $i, $model, string $hsnField): void
    {
        if (! $model) {
            return;
        }
        $this->items[$i]['description'] = $model->name;
        $this->items[$i]['hsn_code'] = $model->{$hsnField};
        $this->items[$i]['uom_id'] = $model->uom_id ?? null;
        $this->items[$i]['cost_rate'] = (float) $model->rate_before_tax;
        $this->items[$i]['unit_rate'] = (float) $model->rate_before_tax;
        $this->items[$i]['tax_id'] = $model->tax_id;
        $this->items[$i]['tax_percent'] = (float) (($model->tax?->gst_percent ?? 0) + ($model->tax?->cess_percent ?? 0));
    }

    public function addLine(string $type): void
    {
        $this->items[] = [
            'id' => null, 'line_type' => $type, 'spare_id' => null, 'labour_id' => null, 'uom_id' => null,
            'tax_id' => null, 'description' => '', 'hsn_code' => null, 'qty' => 1,
            'cost_rate' => 0, 'unit_rate' => 0, 'discount_value' => 0, 'tax_percent' => 0,
            'sequence_no' => count($this->items) + 1,
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /**
     * @return array{parts: float, labour: float, discount: float, tax: float, grand: float, cost: float, profit: float, margin: float, balance: float}
     */
    #[Computed]
    public function totals(): array
    {
        $parts = 0.0;
        $labour = 0.0;
        $discount = 0.0;
        $tax = 0.0;
        $cost = 0.0;

        foreach ($this->items as $row) {
            $sell = (float) ($row['qty'] ?? 0) * (float) ($row['unit_rate'] ?? 0);
            $disc = min((float) ($row['discount_value'] ?? 0), $sell);
            $tax += ($sell - $disc) * (float) ($row['tax_percent'] ?? 0) / 100;
            $cost += (float) ($row['qty'] ?? 0) * (float) ($row['cost_rate'] ?? 0);
            $discount += $disc;
            if (($row['line_type'] ?? 'spare') === 'labour') {
                $labour += $sell;
            } else {
                $parts += $sell;
            }
        }

        $subtotal = $parts + $labour;
        $netSell = $subtotal - $discount;
        $profit = round($netSell - $cost, 2);
        $margin = $netSell > 0 ? round($profit / $netSell * 100, 2) : 0.0;
        $grand = round($netSell + $tax, 2);

        return [
            'parts' => round($parts, 2),
            'labour' => round($labour, 2),
            'discount' => round($discount, 2),
            'tax' => round($tax, 2),
            'grand' => $grand,
            'cost' => round($cost, 2),
            'profit' => $profit,
            'margin' => $margin,
            'balance' => round($grand - (float) $this->amount_paid, 2),
        ];
    }

    protected function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'status' => ['required', Rule::in(array_keys(CounterSalesInvoice::statuses()))],
            'payment_status' => ['required', Rule::in(array_keys(CounterSalesInvoice::paymentStatuses()))],
            'delivery_type' => ['required', Rule::in(array_keys(CounterSalesInvoice::deliveryTypes()))],
            'courier_company_id' => ['nullable', 'integer', 'exists:courier_companies,id'],
            'transport_mode_id' => ['nullable', 'integer', 'exists:transport_modes,id'],
            'department_id' => ['nullable', 'integer', 'exists:workshop_departments,id'],
            'loss_type_id' => ['nullable', 'integer', 'exists:loss_types,id'],
            'payment_mode_id' => ['nullable', 'integer', 'exists:payment_modes,id'],
            'cancellation_reason_id' => ['nullable', 'integer', 'exists:invoice_cancellation_reasons,id'],
            'advisor_id' => ['nullable', 'integer', 'exists:employees,id'],
            'technician_id' => ['nullable', 'integer', 'exists:employees,id'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'warranty_type' => ['nullable', Rule::in(array_keys(CounterSalesInvoice::warrantyTypes()))],
            'warranty_period' => ['nullable', Rule::in(array_keys(CounterSalesInvoice::warrantyPeriods()))],
            'discount_type' => ['nullable', Rule::in(array_keys(CounterSalesInvoice::discountTypes()))],
            'tracking_no' => ['nullable', 'string', 'max:60'],
            'delivery_address' => ['nullable', 'string', 'max:1000'],
            'recommended_service' => ['nullable', 'string', 'max:2000'],
            'amount_paid' => ['numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array'],
            'items.*.line_type' => ['required', 'in:spare,labour'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['numeric', 'min:0.01'],
            'items.*.cost_rate' => ['numeric', 'min:0'],
            'items.*.unit_rate' => ['numeric', 'min:0'],
            'items.*.discount_value' => ['numeric', 'min:0'],
            'items.*.tax_percent' => ['numeric', 'min:0', 'max:100'],
        ];
    }

    #[Computed]
    public function customers()
    {
        $rows = CustomerMaster::query()->where('is_active', true)->orderByDesc('id')->limit(300)
            ->get(['id', 'first_name', 'last_name', 'phone']);

        if ($this->customer_id && ! $rows->contains('id', $this->customer_id)) {
            $sel = CustomerMaster::find($this->customer_id);
            if ($sel) {
                $rows->prepend($sel);
            }
        }

        return $rows->map(fn ($c) => [
            'id' => $c->id,
            'label' => trim($c->first_name.' '.($c->last_name ?? '')).($c->phone ? ' · '.$c->phone : ''),
        ]);
    }

    #[Computed]
    public function courierCompanies()
    {
        return CourierCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function transportModes()
    {
        return TransportModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function departments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function lossTypes()
    {
        return LossTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function paymentModes()
    {
        return PaymentModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function cancellationReasons()
    {
        return InvoiceCancellationReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function vendors()
    {
        return VendorMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function spares()
    {
        return SpareMaster::query()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name']);
    }

    #[Computed]
    public function labours()
    {
        return LabourMaster::query()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name']);
    }

    #[Computed]
    public function uoms()
    {
        return UnitOfMeasureMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function taxes()
    {
        return TaxMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'counter_sales_invoice.update' : 'counter_sales_invoice.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $totals = $this->totals();
        $data['parts_total'] = $totals['parts'];
        $data['labour_total'] = $totals['labour'];
        $data['discount_total'] = $totals['discount'];
        $data['tax_total'] = $totals['tax'];
        $data['grand_total'] = $totals['grand'];
        $data['cost_total'] = $totals['cost'];
        $data['profit_total'] = $totals['profit'];
        $data['margin_percent'] = $totals['margin'];
        $data['balance_due'] = $totals['balance'];

        $existing = $this->editingId ? CounterSalesInvoice::find($this->editingId) : null;
        if ($data['status'] === 'finalized' && (! $existing || ! $existing->invoiced_at)) {
            $data['invoiced_at'] = now();
        }
        if ($data['payment_status'] === 'fully_paid' && (! $existing || ! $existing->paid_at)) {
            $data['paid_at'] = now();
        }
        if (in_array($data['status'], ['cancelled', 'credit_note'], true)) {
            $data['cancelled_at'] = now();
        }

        foreach (['tracking_no', 'delivery_address', 'recommended_service', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $invoice = DB::transaction(function () use ($data, $items, $isCreate) {
            if ($isCreate) {
                $row = CounterSalesInvoice::create($data);
                $this->editingId = $row->id;
                $this->invoice_no = $row->fresh()->invoice_no;
            } else {
                $row = CounterSalesInvoice::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncStockDeductions($row);

            return $row;
        });

        $stockMsg = in_array($invoice->status, ['cancelled', 'credit_note'], true) ? '' : ' — stock deducted';
        Flux::toast(text: 'Counter invoice '.$invoice->fresh()->invoice_no.($isCreate ? ' created' : ' updated').$stockMsg.'.', variant: 'success');

        if ($isCreate) {
            return redirect()->route('counter-sales-invoice.edit', $invoice->id);
        }

        return redirect()->route('counter-sales-invoice.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncItems(CounterSalesInvoice $invoice, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $local = $this->items[$i] ?? [];
            $sell = (float) ($row['qty'] ?? 0) * (float) ($row['unit_rate'] ?? 0);
            $disc = min((float) ($row['discount_value'] ?? 0), $sell);
            $lineTotal = round(($sell - $disc) * (1 + (float) ($row['tax_percent'] ?? 0) / 100), 2);

            $payload = [
                'line_type' => $row['line_type'],
                'spare_id' => $row['line_type'] === 'spare' ? ($local['spare_id'] ?? null) : null,
                'labour_id' => $row['line_type'] === 'labour' ? ($local['labour_id'] ?? null) : null,
                'uom_id' => $local['uom_id'] ?? null,
                'tax_id' => $local['tax_id'] ?? null,
                'description' => strtoupper((string) $row['description']),
                'hsn_code' => $local['hsn_code'] ?? null,
                'qty' => (float) ($row['qty'] ?? 1),
                'cost_rate' => (float) ($row['cost_rate'] ?? 0),
                'unit_rate' => (float) ($row['unit_rate'] ?? 0),
                'discount_value' => (float) ($row['discount_value'] ?? 0),
                'tax_percent' => (float) ($row['tax_percent'] ?? 0),
                'line_total' => $lineTotal,
                'sequence_no' => $i + 1,
            ];

            if (! empty($local['id'])) {
                $ex = $invoice->items()->whereKey($local['id'])->first();
                if ($ex) {
                    $ex->update($payload);
                    $keptIds[] = $ex->id;

                    continue;
                }
            }

            $created = $invoice->items()->create($payload);
            $keptIds[] = $created->id;
            $this->items[$i]['id'] = $created->id;
        }

        $invoice->items()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Auto-deduct stock: a counter sale is the stock-out event. Every spare line posts a
     * negative SALE entry, re-synced on each save so quantities never drift. Cancelled /
     * credit-note invoices post nothing (any prior movement is removed).
     */
    protected function syncStockDeductions(CounterSalesInvoice $invoice): void
    {
        StockEntry::query()
            ->where('source_type', CounterSalesInvoice::class)
            ->where('source_id', $invoice->id)
            ->delete();

        if (in_array($invoice->status, ['cancelled', 'credit_note'], true)) {
            return;
        }

        $movedAt = $invoice->invoiced_at ?? now();

        foreach ($invoice->items()->where('line_type', 'spare')->whereNotNull('spare_id')->get() as $item) {
            if ((float) $item->qty <= 0) {
                continue;
            }
            StockEntry::create([
                'spare_id' => $item->spare_id,
                'entry_type' => StockEntry::TYPE_SALE,
                'source_type' => CounterSalesInvoice::class,
                'source_id' => $invoice->id,
                'qty' => -1 * (float) $item->qty,
                'rate_per_unit' => (float) $item->unit_rate,
                'moved_at' => $movedAt,
                'actor_user_id' => auth()->id(),
                'notes' => 'Counter Sales Invoice '.$invoice->invoice_no,
            ]);
        }
    }

    public function render()
    {
        return view('counter-sales-invoice::edit');
    }
}
