<?php

namespace App\Modules\SalesReturn\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CounterSalesInvoice\Models\CounterSalesInvoice;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\Inventory\Models\StockEntry;
use App\Modules\InvoiceCancellationReasonMaster\Models\InvoiceCancellationReasonMaster;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\RegularSalesInvoice\Models\RegularSalesInvoice;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\SalesReturnReasonMaster\Models\SalesReturnReasonMaster;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Sales Return')]
class Edit extends Component
{
    use SearchesPickerOptions;

    /** Search term for the server-backed vendors picker. */
    public string $vendorSearch = '';

    public ?int $editingId = null;

    public ?string $return_no = null;

    public string $return_type = 'regular';

    public string $reference_mode = 'item_wise';

    public string $status = 'draft';

    public string $refund_status = 'unpaid';

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $regular_sales_invoice_id = null;

    public ?int $counter_sales_invoice_id = null;

    public ?int $insurance_company_id = null;

    public ?int $service_package_id = null;

    public ?int $amc_package_id = null;

    public ?int $department_id = null;

    public ?int $advisor_id = null;

    public ?int $technician_id = null;

    public ?int $vendor_id = null;

    public ?int $sales_return_reason_id = null;

    public ?int $cancellation_reason_id = null;

    public ?string $discount_type = null;

    public float $refunded_amount = 0;

    public ?string $notes = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    #[Url(as: 'from-regular-invoice')]
    public ?int $fromRegularInvoice = null;

    #[Url(as: 'from-counter-invoice')]
    public ?int $fromCounterInvoice = null;

    /** @var list<array<string, mixed>> */
    public array $items = [];

    public function mount(?SalesReturn $salesReturn = null): void
    {
        if ($salesReturn && $salesReturn->exists) {
            $this->load($salesReturn);

            return;
        }

        if ($this->fromRegularInvoice) {
            $this->applyRegularInvoice($this->fromRegularInvoice);

            return;
        }

        if ($this->fromCounterInvoice) {
            $this->applyCounterInvoice($this->fromCounterInvoice);
        }
    }

    protected function load(SalesReturn $sr): void
    {
        $sr->load('items');

        $this->editingId = $sr->id;
        foreach ([
            'return_no', 'return_type', 'reference_mode', 'status', 'refund_status', 'customer_id', 'customer_vehicle_id',
            'regular_sales_invoice_id', 'counter_sales_invoice_id', 'insurance_company_id', 'service_package_id',
            'amc_package_id', 'department_id', 'advisor_id', 'technician_id', 'vendor_id', 'sales_return_reason_id',
            'cancellation_reason_id', 'discount_type', 'notes',
        ] as $k) {
            $this->{$k} = $sr->{$k};
        }
        $this->refunded_amount = (float) $sr->refunded_amount;

        $this->items = $sr->items->map(fn ($i) => [
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

    /** Snapshot a regular / insurance invoice into a fresh return. */
    protected function applyRegularInvoice(int $invoiceId): void
    {
        $inv = RegularSalesInvoice::with('items')->find($invoiceId);
        if (! $inv) {
            return;
        }

        $this->return_type = $inv->invoice_type === 'insurance' ? 'insurance' : 'regular';
        $this->regular_sales_invoice_id = $inv->id;
        $this->customer_id = $inv->customer_id;
        $this->customer_vehicle_id = $inv->customer_vehicle_id;
        $this->insurance_company_id = $inv->insurance_company_id;
        $this->service_package_id = $inv->service_package_id;
        $this->amc_package_id = $inv->amc_package_id;
        $this->department_id = $inv->department_id;
        $this->advisor_id = $inv->advisor_id;
        $this->technician_id = $inv->technician_id;
        $this->vendor_id = $inv->vendor_id;

        $this->snapshotItems($inv->items);
    }

    /** Snapshot a counter invoice into a fresh return. */
    protected function applyCounterInvoice(int $invoiceId): void
    {
        $inv = CounterSalesInvoice::with('items')->find($invoiceId);
        if (! $inv) {
            return;
        }

        $this->return_type = 'counter';
        $this->counter_sales_invoice_id = $inv->id;
        $this->customer_id = $inv->customer_id;
        $this->department_id = $inv->department_id;
        $this->advisor_id = $inv->advisor_id;
        $this->technician_id = $inv->technician_id;
        $this->vendor_id = $inv->vendor_id;

        $this->snapshotItems($inv->items);
    }

    /**
     * @param  Collection<int, Model>  $sourceItems
     */
    protected function snapshotItems($sourceItems): void
    {
        $this->items = $sourceItems->values()->map(fn ($i, $idx) => [
            'id' => null,
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
            'sequence_no' => $idx + 1,
        ])->all();
    }

    public function updated(string $name, $value): void
    {
        if ($name === 'customer_vehicle_id' && $value) {
            $vehicle = CustomerVehicleMaster::find($value);
            if ($vehicle) {
                $this->customer_id = $vehicle->customer_id;
            }
        }
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
     * @return array{parts: float, labour: float, discount: float, tax: float, grand: float, balance: float}
     */
    #[Computed]
    public function totals(): array
    {
        $parts = 0.0;
        $labour = 0.0;
        $discount = 0.0;
        $tax = 0.0;

        foreach ($this->items as $row) {
            $sell = (float) ($row['qty'] ?? 0) * (float) ($row['unit_rate'] ?? 0);
            $disc = min((float) ($row['discount_value'] ?? 0), $sell);
            $tax += ($sell - $disc) * (float) ($row['tax_percent'] ?? 0) / 100;
            $discount += $disc;
            if (($row['line_type'] ?? 'spare') === 'labour') {
                $labour += $sell;
            } else {
                $parts += $sell;
            }
        }

        $grand = round($parts + $labour - $discount + $tax, 2);

        return [
            'parts' => round($parts, 2),
            'labour' => round($labour, 2),
            'discount' => round($discount, 2),
            'tax' => round($tax, 2),
            'grand' => $grand,
            'balance' => round($grand - (float) $this->refunded_amount, 2),
        ];
    }

    protected function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'customer_vehicle_id' => ['nullable', 'integer', 'exists:customer_vehicles,id'],
            'return_type' => ['required', Rule::in(array_keys(SalesReturn::returnTypes()))],
            'reference_mode' => ['required', Rule::in(array_keys(SalesReturn::referenceModes()))],
            'status' => ['required', Rule::in(array_keys(SalesReturn::statuses()))],
            'refund_status' => ['required', Rule::in(array_keys(SalesReturn::refundStatuses()))],
            'regular_sales_invoice_id' => ['nullable', 'integer', 'exists:regular_sales_invoices,id'],
            'counter_sales_invoice_id' => ['nullable', 'integer', 'exists:counter_sales_invoices,id'],
            'insurance_company_id' => ['nullable', 'integer', 'exists:insurance_companies,id'],
            'service_package_id' => ['nullable', 'integer', 'exists:service_packages,id'],
            'amc_package_id' => ['nullable', 'integer', 'exists:service_packages,id'],
            'department_id' => ['nullable', 'integer', 'exists:workshop_departments,id'],
            'advisor_id' => ['nullable', 'integer', 'exists:employees,id'],
            'technician_id' => ['nullable', 'integer', 'exists:employees,id'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'sales_return_reason_id' => ['nullable', 'integer', 'exists:sales_return_reasons,id'],
            'cancellation_reason_id' => ['nullable', 'integer', 'exists:invoice_cancellation_reasons,id'],
            'discount_type' => ['nullable', Rule::in(array_keys(SalesReturn::discountTypes()))],
            'refunded_amount' => ['numeric', 'min:0'],
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
    public function vehiclePickerOptions()
    {
        $rows = CustomerVehicleMaster::query()
            ->with(['model.brand:id,name', 'customer:id,first_name,last_name'])
            ->where('is_active', true)->orderByDesc('id')->limit(300)
            ->get(['id', 'registration_no', 'model_id', 'customer_id']);

        if ($this->customer_vehicle_id && ! $rows->contains('id', $this->customer_vehicle_id)) {
            $sel = CustomerVehicleMaster::with(['model.brand:id,name', 'customer:id,first_name,last_name'])->find($this->customer_vehicle_id);
            if ($sel) {
                $rows->prepend($sel);
            }
        }

        return $rows->map(fn ($v) => [
            'id' => $v->id,
            'label' => trim(($v->model?->brand?->name ?? '').' '.($v->model?->name ?? '')).' — '.$v->registration_no,
        ]);
    }

    #[Computed]
    public function regularInvoices()
    {
        return RegularSalesInvoice::query()->orderByDesc('created_at')->limit(100)->get(['id', 'invoice_no']);
    }

    #[Computed]
    public function counterInvoices()
    {
        return CounterSalesInvoice::query()->orderByDesc('created_at')->limit(100)->get(['id', 'invoice_no']);
    }

    #[Computed]
    public function insuranceCompanies()
    {
        return InsuranceCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function servicePackages()
    {
        return ServicePackageMaster::query()->where('is_active', true)->where('is_amc', false)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function amcPackages()
    {
        return ServicePackageMaster::query()->where('is_active', true)->where('is_amc', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function departments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function vendors()
    {
        return $this->pickerOptions(
            query: VendorMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'vendor_code'],
            term: $this->vendorSearch,
            selected: $this->vendor_id,
            columns: ['id', 'name'],
            limit: 20,
        );
    }

    #[Computed]
    public function returnReasons()
    {
        return SalesReturnReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function cancellationReasons()
    {
        return InvoiceCancellationReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
        $this->authorize($this->editingId ? 'sales_return.update' : 'sales_return.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $totals = $this->totals();
        $data['parts_total'] = $totals['parts'];
        $data['labour_total'] = $totals['labour'];
        $data['discount_total'] = $totals['discount'];
        $data['tax_total'] = $totals['tax'];
        $data['grand_total'] = $totals['grand'];
        $data['balance_refund'] = $totals['balance'];

        $existing = $this->editingId ? SalesReturn::find($this->editingId) : null;
        if ($data['status'] === 'finalized' && (! $existing || ! $existing->returned_at)) {
            $data['returned_at'] = now();
        }
        if ($data['refund_status'] === 'fully_refunded' && (! $existing || ! $existing->refunded_at)) {
            $data['refunded_at'] = now();
        }
        if ($data['status'] === 'cancelled') {
            $data['cancelled_at'] = now();
        }

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }

        $isCreate = $this->editingId === null;

        $return = DB::transaction(function () use ($data, $items, $isCreate) {
            if ($isCreate) {
                $row = SalesReturn::create($data);
                $this->editingId = $row->id;
                $this->return_no = $row->fresh()->return_no;
            } else {
                $row = SalesReturn::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncStockReturns($row);

            return $row;
        });

        $stockMsg = $return->status === 'cancelled' ? '' : ' — stock restored';
        Flux::toast(text: 'Sales return '.$return->fresh()->return_no.($isCreate ? ' created' : ' updated').$stockMsg.'.', variant: 'success');

        if ($isCreate) {
            return redirect()->route('sales-return.edit', $return->id);
        }

        return redirect()->route('sales-return.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncItems(SalesReturn $return, array $rows): void
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
                $ex = $return->items()->whereKey($local['id'])->first();
                if ($ex) {
                    $ex->update($payload);
                    $keptIds[] = $ex->id;

                    continue;
                }
            }

            $created = $return->items()->create($payload);
            $keptIds[] = $created->id;
            $this->items[$i]['id'] = $created->id;
        }

        $return->items()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * A sales return puts sold spares back into inventory. Every spare line posts a
     * positive SALE_RETURN entry, re-synced on each save so quantities never drift.
     * Cancelled returns post nothing (any prior movement is removed).
     */
    protected function syncStockReturns(SalesReturn $return): void
    {
        StockEntry::query()
            ->where('source_type', SalesReturn::class)
            ->where('source_id', $return->id)
            ->delete();

        if ($return->status === 'cancelled') {
            return;
        }

        $movedAt = $return->returned_at ?? now();

        foreach ($return->items()->where('line_type', 'spare')->whereNotNull('spare_id')->get() as $item) {
            if ((float) $item->qty <= 0) {
                continue;
            }
            StockEntry::create([
                'spare_id' => $item->spare_id,
                'entry_type' => StockEntry::TYPE_SALE_RETURN,
                'source_type' => SalesReturn::class,
                'source_id' => $return->id,
                'qty' => (float) $item->qty,
                'rate_per_unit' => (float) $item->unit_rate,
                'moved_at' => $movedAt,
                'actor_user_id' => auth()->id(),
                'notes' => 'Sales Return '.$return->return_no,
            ]);
        }
    }

    public function render()
    {
        return view('sales-return::edit');
    }
}
