<?php

namespace App\Modules\ReceiptRefund\Livewire;

use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\ChequeBounceReasonMaster\Models\ChequeBounceReasonMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use App\Modules\ReceiptCancellationReasonMaster\Models\ReceiptCancellationReasonMaster;
use App\Modules\ReceiptRefund\Models\ReceiptRefund;
use App\Modules\RefundTypeMaster\Models\RefundTypeMaster;
use App\Modules\RegularReceipt\Models\RegularReceipt;
use App\Modules\RegularSalesInvoice\Models\RegularSalesInvoice;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
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
#[Title('Receipt Refund')]
class Edit extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $refund_no = null;

    public string $refund_against = 'regular_receipt';

    public string $refund_status = 'requested';

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $insurance_company_id = null;

    public ?int $department_id = null;

    public ?int $service_type_id = null;

    public ?int $refund_type_id = null;

    public ?int $advance_receipt_id = null;

    public ?int $regular_receipt_id = null;

    public ?int $job_card_id = null;

    public ?int $sales_estimate_id = null;

    public ?int $regular_sales_invoice_id = null;

    public ?int $sales_return_id = null;

    public ?int $advisor_id = null;

    public ?int $refunded_by_id = null;

    public float $amount = 0;

    public ?int $refund_mode_id = null;

    public ?int $bank_id = null;

    public ?string $cheque_no = null;

    public ?string $cheque_date = null;

    public ?string $cheque_status = null;

    public ?int $cheque_bounce_reason_id = null;

    public ?int $cancellation_reason_id = null;

    public ?string $reference_no = null;

    public ?string $notes = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    #[Url(as: 'from-receipt')]
    public ?int $fromReceipt = null;

    public ?string $attachmentType = null;

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachmentFiles = [];

    /** @var list<int> */
    public array $removedAttachmentIds = [];

    public function mount(?ReceiptRefund $receiptRefund = null): void
    {
        if ($receiptRefund && $receiptRefund->exists) {
            $this->load($receiptRefund);

            return;
        }

        if ($this->fromReceipt) {
            $receipt = RegularReceipt::find($this->fromReceipt);
            if ($receipt) {
                $this->regular_receipt_id = $receipt->id;
                $this->customer_id = $receipt->customer_id;
                $this->insurance_company_id = $receipt->insurance_company_id;
                $this->amount = (float) $receipt->amount;
            }
        }
    }

    protected function load(ReceiptRefund $r): void
    {
        $this->editingId = $r->id;
        foreach ([
            'refund_no', 'refund_against', 'refund_status', 'customer_id', 'customer_vehicle_id',
            'insurance_company_id', 'department_id', 'service_type_id', 'refund_type_id', 'advance_receipt_id',
            'regular_receipt_id', 'job_card_id', 'sales_estimate_id', 'regular_sales_invoice_id', 'sales_return_id',
            'advisor_id', 'refunded_by_id', 'refund_mode_id', 'bank_id', 'cheque_no', 'cheque_status',
            'cheque_bounce_reason_id', 'cancellation_reason_id', 'reference_no', 'notes',
        ] as $k) {
            $this->{$k} = $r->{$k};
        }
        $this->amount = (float) $r->amount;
        $this->cheque_date = $r->cheque_date?->format('Y-m-d');
    }

    public function updated(string $name, $value): void
    {
        if ($name === 'customer_vehicle_id' && $value) {
            $vehicle = CustomerVehicleMaster::find($value);
            if ($vehicle) {
                $this->customer_id = $vehicle->customer_id;
            }
        }
    }

    public function removeAttachment(int $id): void
    {
        if (! in_array($id, $this->removedAttachmentIds, true)) {
            $this->removedAttachmentIds[] = $id;
        }
    }

    protected function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'refund_against' => ['required', Rule::in(array_keys(ReceiptRefund::refundAgainstOptions()))],
            'refund_status' => ['required', Rule::in(array_keys(ReceiptRefund::refundStatuses()))],
            'customer_vehicle_id' => ['nullable', 'integer', 'exists:customer_vehicles,id'],
            'insurance_company_id' => ['nullable', 'integer', 'exists:insurance_companies,id'],
            'department_id' => ['nullable', 'integer', 'exists:workshop_departments,id'],
            'service_type_id' => ['nullable', 'integer', 'exists:service_types,id'],
            'refund_type_id' => ['nullable', 'integer', 'exists:refund_types,id'],
            'advance_receipt_id' => ['nullable', 'integer', 'exists:regular_receipts,id'],
            'regular_receipt_id' => ['nullable', 'integer', 'exists:regular_receipts,id'],
            'job_card_id' => ['nullable', 'integer', 'exists:job_cards,id'],
            'sales_estimate_id' => ['nullable', 'integer', 'exists:sales_estimates,id'],
            'regular_sales_invoice_id' => ['nullable', 'integer', 'exists:regular_sales_invoices,id'],
            'sales_return_id' => ['nullable', 'integer', 'exists:sales_returns,id'],
            'advisor_id' => ['nullable', 'integer', 'exists:employees,id'],
            'refunded_by_id' => ['nullable', 'integer', 'exists:employees,id'],
            'amount' => ['numeric', 'min:0'],
            'refund_mode_id' => ['nullable', 'integer', 'exists:payment_modes,id'],
            'bank_id' => ['nullable', 'integer', 'exists:banks,id'],
            'cheque_no' => ['nullable', 'string', 'max:40'],
            'cheque_date' => ['nullable', 'date'],
            'cheque_status' => ['nullable', Rule::in(array_keys(ReceiptRefund::chequeStatuses()))],
            'cheque_bounce_reason_id' => ['nullable', 'integer', 'exists:cheque_bounce_reasons,id'],
            'cancellation_reason_id' => ['nullable', 'integer', 'exists:receipt_cancellation_reasons,id'],
            'reference_no' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachmentType' => ['nullable', Rule::in(array_keys(ReceiptRefund::attachmentTypes()))],
            'attachmentFiles.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
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
            ->with(['model.brand:id,name'])
            ->where('is_active', true)->orderByDesc('id')->limit(300)
            ->get(['id', 'registration_no', 'model_id', 'customer_id']);

        if ($this->customer_vehicle_id && ! $rows->contains('id', $this->customer_vehicle_id)) {
            $sel = CustomerVehicleMaster::with(['model.brand:id,name'])->find($this->customer_vehicle_id);
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
    public function insuranceCompanies()
    {
        return InsuranceCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
    public function refundTypes()
    {
        return RefundTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function receipts()
    {
        return RegularReceipt::query()->orderByDesc('created_at')->limit(100)->get(['id', 'receipt_no']);
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
    public function regularInvoices()
    {
        return RegularSalesInvoice::query()->orderByDesc('created_at')->limit(100)->get(['id', 'invoice_no']);
    }

    #[Computed]
    public function salesReturns()
    {
        return SalesReturn::query()->orderByDesc('created_at')->limit(100)->get(['id', 'return_no']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function refundModes()
    {
        return PaymentModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function banks()
    {
        return BankMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function chequeBounceReasons()
    {
        return ChequeBounceReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function cancellationReasons()
    {
        return ReceiptCancellationReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function existingAttachments()
    {
        if (! $this->editingId) {
            return collect();
        }

        return ReceiptRefund::findOrFail($this->editingId)->attachments()
            ->whereNotIn('id', $this->removedAttachmentIds)
            ->get();
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'receipt_refund.update' : 'receipt_refund.create');

        $data = $this->validate();
        unset($data['attachmentFiles'], $data['attachmentType']);

        $existing = $this->editingId ? ReceiptRefund::find($this->editingId) : null;
        if ($data['refund_status'] === 'refunded' && (! $existing || ! $existing->refunded_at)) {
            $data['refunded_at'] = now();
        }
        if ($data['refund_status'] === 'cancelled') {
            $data['cancelled_at'] = now();
        }

        foreach (['cheque_no', 'reference_no', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $refund = DB::transaction(function () use ($data, $isCreate) {
            if ($isCreate) {
                $row = ReceiptRefund::create($data);
                $this->editingId = $row->id;
                $this->refund_no = $row->fresh()->refund_no;
            } else {
                $row = ReceiptRefund::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncAttachments($row);

            return $row;
        });

        $this->attachmentFiles = [];
        $this->removedAttachmentIds = [];

        Flux::toast(text: 'Refund '.$refund->fresh()->refund_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        if ($isCreate) {
            return redirect()->route('receipt-refund.edit', $refund->id);
        }

        return redirect()->route('receipt-refund.index');
    }

    protected function syncAttachments(ReceiptRefund $refund): void
    {
        if ($this->removedAttachmentIds) {
            foreach ($refund->attachments()->whereIn('id', $this->removedAttachmentIds)->get() as $att) {
                Storage::disk('public')->delete($att->path);
                $att->delete();
            }
        }

        foreach ($this->attachmentFiles as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }
            $refund->attachments()->create([
                'attachment_type' => $this->attachmentType,
                'path' => $file->store("receipt-refunds/{$refund->id}/attachments", 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        }
    }

    public function render()
    {
        return view('receipt-refund::edit');
    }
}
