<?php

namespace App\Modules\RegularSalesInvoice\Livewire;

use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\InsuranceDeductionTypeMaster\Models\InsuranceDeductionTypeMaster;
use App\Modules\InvoiceCancellationReasonMaster\Models\InvoiceCancellationReasonMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\LossReasonMaster\Models\LossReasonMaster;
use App\Modules\LossTypeMaster\Models\LossTypeMaster;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use App\Modules\Proforma\Models\Proforma;
use App\Modules\RegularSalesInvoice\Models\RegularSalesInvoice;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Regular Sales Invoice')]
class Edit extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $invoice_no = null;

    public string $invoice_type = 'regular';

    public string $status = 'draft';

    public string $payment_status = 'unpaid';

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $job_card_id = null;

    public ?int $sales_estimate_id = null;

    public ?int $proforma_id = null;

    public ?int $department_id = null;

    public ?int $service_type_id = null;

    public ?int $insurance_company_id = null;

    public ?int $service_package_id = null;

    public ?int $amc_package_id = null;

    public ?int $loss_type_id = null;

    public ?int $loss_reason_id = null;

    public ?int $payment_mode_id = null;

    public ?int $cancellation_reason_id = null;

    public ?int $advisor_id = null;

    public ?int $technician_id = null;

    public ?int $vendor_id = null;

    public ?string $warranty_type = null;

    public ?string $warranty_period = null;

    public ?string $discount_type = null;

    public ?string $policy_no = null;

    public ?string $recommended_service = null;

    public float $amount_paid = 0;

    public ?string $notes = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    #[Url(as: 'from-job-card')]
    public ?int $fromJobCard = null;

    #[Url(as: 'from-proforma')]
    public ?int $fromProforma = null;

    /** @var list<array<string, mixed>> */
    public array $items = [];

    /** @var list<array{id: ?int, insurance_deduction_type_id: ?int, amount: float|string, notes: ?string}> */
    public array $deductions = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachmentFiles = [];

    /** @var list<int> */
    public array $removedAttachmentIds = [];

    public function mount(?RegularSalesInvoice $regularSalesInvoice = null): void
    {
        if ($regularSalesInvoice && $regularSalesInvoice->exists) {
            $this->load($regularSalesInvoice);

            return;
        }

        if ($this->fromProforma) {
            $this->applyProforma($this->fromProforma);

            return;
        }

        if ($this->fromJobCard) {
            $jc = JobCard::find($this->fromJobCard);
            if ($jc) {
                $this->job_card_id = $jc->id;
                $this->customer_id = $jc->customer_id;
                $this->customer_vehicle_id = $jc->customer_vehicle_id;
            }
        }
    }

    protected function load(RegularSalesInvoice $inv): void
    {
        $inv->load(['items', 'deductions']);

        $this->editingId = $inv->id;
        foreach ([
            'invoice_no', 'invoice_type', 'status', 'payment_status', 'customer_id', 'customer_vehicle_id',
            'job_card_id', 'sales_estimate_id', 'proforma_id', 'department_id', 'service_type_id',
            'insurance_company_id', 'service_package_id', 'amc_package_id', 'loss_type_id', 'loss_reason_id',
            'payment_mode_id', 'cancellation_reason_id', 'advisor_id', 'technician_id', 'vendor_id',
            'warranty_type', 'warranty_period', 'discount_type', 'policy_no', 'recommended_service', 'notes',
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

        $this->deductions = $inv->deductions->map(fn ($d) => [
            'id' => $d->id,
            'insurance_deduction_type_id' => $d->insurance_deduction_type_id,
            'amount' => (float) $d->amount,
            'notes' => $d->notes,
        ])->all();
    }

    /**
     * Convert a proforma into a fresh (unsaved) invoice — snapshot header + line items.
     */
    protected function applyProforma(int $proformaId): void
    {
        $pf = Proforma::with('items')->find($proformaId);
        if (! $pf) {
            return;
        }

        $this->proforma_id = $pf->id;
        $this->customer_id = $pf->customer_id;
        $this->customer_vehicle_id = $pf->customer_vehicle_id;
        $this->job_card_id = $pf->job_card_id;
        $this->sales_estimate_id = $pf->sales_estimate_id;
        $this->department_id = $pf->department_id;
        $this->service_type_id = $pf->service_type_id;
        $this->insurance_company_id = $pf->insurance_company_id;
        $this->service_package_id = $pf->service_package_id;
        $this->loss_type_id = $pf->loss_type_id;
        $this->loss_reason_id = $pf->loss_reason_id;
        $this->advisor_id = $pf->advisor_id;
        $this->technician_id = $pf->technician_id;
        $this->vendor_id = $pf->vendor_id;
        $this->warranty_type = $pf->warranty_type;
        $this->warranty_period = $pf->warranty_period;
        $this->discount_type = $pf->discount_type;
        $this->policy_no = $pf->policy_no;
        $this->recommended_service = $pf->recommended_service;
        $this->invoice_type = $pf->insurance_company_id ? 'insurance' : 'regular';

        $this->items = $pf->items->map(fn ($i, $idx) => [
            'id' => null,
            'line_type' => $i->line_type,
            'spare_id' => $i->spare_id,
            'labour_id' => $i->labour_id,
            'uom_id' => null,
            'tax_id' => $i->tax_id,
            'description' => $i->description,
            'hsn_code' => $i->hsn_code,
            'qty' => (float) $i->qty,
            'cost_rate' => (float) $i->cost_rate,
            'unit_rate' => (float) $i->unit_rate,
            'discount_value' => (float) $i->discount_value,
            'tax_percent' => (float) $i->tax_percent,
            'sequence_no' => $idx + 1,
        ])->values()->all();
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

    public function addDeduction(): void
    {
        $this->deductions[] = ['id' => null, 'insurance_deduction_type_id' => null, 'amount' => 0, 'notes' => null];
    }

    public function removeDeduction(int $index): void
    {
        unset($this->deductions[$index]);
        $this->deductions = array_values($this->deductions);
    }

    public function removeAttachment(int $id): void
    {
        if (! in_array($id, $this->removedAttachmentIds, true)) {
            $this->removedAttachmentIds[] = $id;
        }
    }

    /**
     * @return array{parts: float, labour: float, discount: float, tax: float, grand: float, cost: float, profit: float, margin: float, deductions: float, balance: float}
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

        $deductions = 0.0;
        foreach ($this->deductions as $d) {
            $deductions += (float) ($d['amount'] ?? 0);
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
            'deductions' => round($deductions, 2),
            'balance' => round($grand - (float) $this->amount_paid, 2),
        ];
    }

    protected function rules(): array
    {
        return [
            'customer_vehicle_id' => ['required', 'integer', 'exists:customer_vehicles,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'invoice_type' => ['required', Rule::in(array_keys(RegularSalesInvoice::invoiceTypes()))],
            'status' => ['required', Rule::in(array_keys(RegularSalesInvoice::statuses()))],
            'payment_status' => ['required', Rule::in(array_keys(RegularSalesInvoice::paymentStatuses()))],
            'job_card_id' => ['nullable', 'integer', 'exists:job_cards,id'],
            'sales_estimate_id' => ['nullable', 'integer', 'exists:sales_estimates,id'],
            'proforma_id' => ['nullable', 'integer', 'exists:proformas,id'],
            'department_id' => ['nullable', 'integer', 'exists:workshop_departments,id'],
            'service_type_id' => ['nullable', 'integer', 'exists:service_types,id'],
            'insurance_company_id' => ['nullable', 'integer', 'exists:insurance_companies,id'],
            'service_package_id' => ['nullable', 'integer', 'exists:service_packages,id'],
            'amc_package_id' => ['nullable', 'integer', 'exists:service_packages,id'],
            'loss_type_id' => ['nullable', 'integer', 'exists:loss_types,id'],
            'loss_reason_id' => ['nullable', 'integer', 'exists:loss_reasons,id'],
            'payment_mode_id' => ['nullable', 'integer', 'exists:payment_modes,id'],
            'cancellation_reason_id' => ['nullable', 'integer', 'exists:invoice_cancellation_reasons,id'],
            'advisor_id' => ['nullable', 'integer', 'exists:employees,id'],
            'technician_id' => ['nullable', 'integer', 'exists:employees,id'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'warranty_type' => ['nullable', Rule::in(array_keys(RegularSalesInvoice::warrantyTypes()))],
            'warranty_period' => ['nullable', Rule::in(array_keys(RegularSalesInvoice::warrantyPeriods()))],
            'discount_type' => ['nullable', Rule::in(array_keys(RegularSalesInvoice::discountTypes()))],
            'policy_no' => ['nullable', 'string', 'max:60'],
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

            'deductions' => ['array'],
            'deductions.*.insurance_deduction_type_id' => ['nullable', 'integer', 'exists:insurance_deduction_types,id'],
            'deductions.*.amount' => ['numeric', 'min:0'],
            'deductions.*.notes' => ['nullable', 'string', 'max:255'],

            'attachmentFiles.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
        ];
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
            'label' => trim(($v->model?->brand?->name ?? '').' '.($v->model?->name ?? '')).' — '.$v->registration_no
                .' · '.trim($v->customer?->first_name.' '.($v->customer?->last_name ?? '')),
        ]);
    }

    #[Computed]
    public function jobCards()
    {
        return JobCard::query()->orderByDesc('opened_at')->limit(100)->get(['id', 'job_card_no']);
    }

    #[Computed]
    public function estimates()
    {
        return SalesEstimate::query()->orderByDesc('created_at')->limit(100)->get(['id', 'estimate_no']);
    }

    #[Computed]
    public function proformas()
    {
        return Proforma::query()->orderByDesc('created_at')->limit(100)->get(['id', 'proforma_no']);
    }

    #[Computed]
    public function departments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function serviceTypes()
    {
        return ServiceTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
    public function lossTypes()
    {
        return LossTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function lossReasons()
    {
        return LossReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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

    #[Computed]
    public function deductionTypes()
    {
        return InsuranceDeductionTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function existingAttachments()
    {
        if (! $this->editingId) {
            return collect();
        }

        return RegularSalesInvoice::findOrFail($this->editingId)->attachments()
            ->whereNotIn('id', $this->removedAttachmentIds)
            ->get();
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'regular_sales_invoice.update' : 'regular_sales_invoice.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $deductions = $data['deductions'] ?? [];
        unset($data['items'], $data['deductions'], $data['attachmentFiles']);

        $totals = $this->totals();
        $data['parts_total'] = $totals['parts'];
        $data['labour_total'] = $totals['labour'];
        $data['discount_total'] = $totals['discount'];
        $data['tax_total'] = $totals['tax'];
        $data['grand_total'] = $totals['grand'];
        $data['cost_total'] = $totals['cost'];
        $data['profit_total'] = $totals['profit'];
        $data['margin_percent'] = $totals['margin'];
        $data['deductions_total'] = $totals['deductions'];
        $data['balance_due'] = $totals['balance'];

        $existing = $this->editingId ? RegularSalesInvoice::find($this->editingId) : null;
        if ($data['status'] === 'finalized' && (! $existing || ! $existing->invoiced_at)) {
            $data['invoiced_at'] = now();
        }
        if ($data['payment_status'] === 'fully_paid' && (! $existing || ! $existing->paid_at)) {
            $data['paid_at'] = now();
        }
        if (in_array($data['status'], ['cancelled', 'credit_note'], true)) {
            $data['cancelled_at'] = now();
        }

        foreach (['policy_no', 'recommended_service', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $invoice = DB::transaction(function () use ($data, $items, $deductions, $isCreate) {
            if ($isCreate) {
                $row = RegularSalesInvoice::create($data);
                $this->editingId = $row->id;
                $this->invoice_no = $row->fresh()->invoice_no;
            } else {
                $row = RegularSalesInvoice::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncDeductions($row, $deductions);
            $this->syncAttachments($row);

            return $row;
        });

        $this->attachmentFiles = [];
        $this->removedAttachmentIds = [];

        Flux::toast(text: 'Invoice '.$invoice->fresh()->invoice_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        if ($isCreate) {
            return redirect()->route('regular-sales-invoice.edit', $invoice->id);
        }

        return redirect()->route('regular-sales-invoice.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncItems(RegularSalesInvoice $invoice, array $rows): void
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
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncDeductions(RegularSalesInvoice $invoice, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $local = $this->deductions[$i] ?? [];
            $payload = [
                'insurance_deduction_type_id' => $local['insurance_deduction_type_id'] ?? null,
                'amount' => (float) ($row['amount'] ?? 0),
                'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
                'sequence_no' => $i + 1,
            ];

            if (! empty($local['id'])) {
                $ex = $invoice->deductions()->whereKey($local['id'])->first();
                if ($ex) {
                    $ex->update($payload);
                    $keptIds[] = $ex->id;

                    continue;
                }
            }

            $created = $invoice->deductions()->create($payload);
            $keptIds[] = $created->id;
            $this->deductions[$i]['id'] = $created->id;
        }

        $invoice->deductions()->whereNotIn('id', $keptIds)->delete();
    }

    protected function syncAttachments(RegularSalesInvoice $invoice): void
    {
        if ($this->removedAttachmentIds) {
            foreach ($invoice->attachments()->whereIn('id', $this->removedAttachmentIds)->get() as $att) {
                Storage::disk('public')->delete($att->path);
                $att->delete();
            }
        }

        foreach ($this->attachmentFiles as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }
            $invoice->attachments()->create([
                'path' => $file->store("regular-sales-invoices/{$invoice->id}/attachments", 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        }
    }

    public function render()
    {
        return view('regular-sales-invoice::edit');
    }
}
