<?php

namespace App\Modules\AdvanceReceipt\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\AdvanceReceipt\Models\AdvanceReceipt;
use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\ChequeBounceReasonMaster\Models\ChequeBounceReasonMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use App\Modules\ReceiptCancellationReasonMaster\Models\ReceiptCancellationReasonMaster;
use App\Modules\ReceiptDifferenceReasonMaster\Models\ReceiptDifferenceReasonMaster;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use App\Support\ChildRows;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Advance Receipt Entry')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $receipt_no = null;

    public ?int $job_card_id = null;

    public ?int $sales_estimate_id = null;

    public ?int $advance_receipt_request_id = null;

    public ?int $insurance_company_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $workshop_department_id = null;

    public ?int $service_type_id = null;

    public ?int $employee_id = null;

    public ?int $payment_mode_id = null;

    public ?int $bank_id = null;

    public ?int $cheque_bounce_reason_id = null;

    public ?int $cancellation_reason_id = null;

    public ?int $difference_reason_id = null;

    public ?float $amount = null;

    public ?float $difference_amount = null;

    public string $payment_status = AdvanceReceipt::STATUS_FULLY_RECEIVED;

    public ?string $reference_no = null;

    public ?string $cheque_no = null;

    public ?string $cheque_date = null;

    public ?string $cheque_status = null;

    public ?string $received_at = null;

    public ?string $notes = null;

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    public string $jobCardSearch = '';

    public string $estimateSearch = '';

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?AdvanceReceipt $advanceReceipt = null): void
    {
        if ($advanceReceipt && $advanceReceipt->exists) {
            $this->load($advanceReceipt);

            return;
        }

        $this->received_at = now()->format('Y-m-d\TH:i');
    }

    protected function load(AdvanceReceipt $r): void
    {
        $r->load('attachments');
        $this->editingId = $r->id;
        foreach ([
            'receipt_no', 'job_card_id', 'sales_estimate_id', 'advance_receipt_request_id', 'insurance_company_id',
            'customer_id', 'customer_vehicle_id', 'workshop_department_id', 'service_type_id', 'employee_id',
            'payment_mode_id', 'bank_id', 'cheque_bounce_reason_id', 'cancellation_reason_id', 'difference_reason_id',
            'payment_status', 'reference_no', 'cheque_no', 'cheque_status', 'notes',
        ] as $k) {
            $this->{$k} = $r->{$k};
        }
        $this->amount = $r->amount === null ? null : (float) $r->amount;
        $this->difference_amount = $r->difference_amount === null ? null : (float) $r->difference_amount;
        $this->cheque_date = $r->cheque_date?->format('Y-m-d');
        $this->received_at = $r->received_at?->format('Y-m-d\TH:i');

        $this->attachments = $r->attachments->map(fn ($a) => [
            'id' => $a->id,
            'attachment_type' => $a->attachment_type,
            'path' => $a->path,
            'original_name' => $a->original_name,
            'notes' => $a->notes,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'sales_estimate_id' => ['nullable', 'integer', Rule::exists('sales_estimates', 'id')],
            'advance_receipt_request_id' => ['nullable', 'integer', Rule::exists('advance_receipt_requests', 'id')],
            'insurance_company_id' => ['nullable', 'integer', Rule::exists('insurance_companies', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'payment_mode_id' => ['required', 'integer', Rule::exists('payment_modes', 'id')],
            'bank_id' => ['nullable', 'integer', Rule::exists('banks', 'id')],
            'cheque_bounce_reason_id' => ['nullable', 'integer', Rule::exists('cheque_bounce_reasons', 'id')],
            'cancellation_reason_id' => ['nullable', 'integer', Rule::exists('receipt_cancellation_reasons', 'id'), Rule::requiredIf(fn () => $this->payment_status === AdvanceReceipt::STATUS_CANCELLED)],
            'difference_reason_id' => ['nullable', 'integer', Rule::exists('receipt_difference_reasons', 'id')],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'difference_amount' => ['nullable', 'numeric'],
            'payment_status' => ['required', Rule::in(array_keys(AdvanceReceipt::paymentStatuses()))],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'cheque_no' => ['nullable', 'string', 'max:50'],
            'cheque_date' => ['nullable', 'date'],
            'cheque_status' => ['nullable', Rule::in(array_keys(AdvanceReceipt::chequeStatuses()))],
            'received_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(AdvanceReceipt::attachmentTypes()))],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ];
    }

    public function addAttachment(): void
    {
        $this->attachments[] = ['id' => null, 'attachment_type' => null, 'path' => null, 'original_name' => null, 'notes' => null];
    }

    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index], $this->attachmentFiles[$index]);
        $this->attachments = array_values($this->attachments);
    }

    #[Computed]
    public function companies()
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
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function paymentModes()
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
    public function differenceReasons()
    {
        return ReceiptDifferenceReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function customers()
    {
        return $this->pickerOptions(
            query: CustomerMaster::query()->where('is_active', true)->orderBy('first_name'),
            searchColumns: ['first_name', 'last_name', 'phone'],
            term: $this->customerSearch,
            selected: $this->customer_id,
            columns: ['id', 'first_name', 'last_name'],
            limit: 30,
        );
    }

    #[Computed]
    public function vehicles()
    {
        return $this->pickerOptions(
            query: CustomerVehicleMaster::query()->orderBy('registration_no'),
            searchColumns: ['registration_no'],
            term: $this->vehicleSearch,
            selected: $this->customer_vehicle_id,
            columns: ['id', 'registration_no'],
            limit: 30,
        );
    }

    #[Computed]
    public function jobCards()
    {
        return $this->pickerOptions(
            query: JobCard::query()->latest('id'),
            searchColumns: ['job_card_no'],
            term: $this->jobCardSearch,
            selected: $this->job_card_id,
            columns: ['id', 'job_card_no'],
            limit: 30,
        );
    }

    #[Computed]
    public function estimates()
    {
        return $this->pickerOptions(
            query: SalesEstimate::query()->latest('id'),
            searchColumns: ['estimate_no'],
            term: $this->estimateSearch,
            selected: $this->sales_estimate_id,
            columns: ['id', 'estimate_no'],
            limit: 30,
        );
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'advance_receipt.update' : 'advance_receipt.create');

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);

        foreach (['reference_no', 'cheque_no', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $receipt = DB::transaction(function () use ($data, $attachments, $isCreate) {
            if ($isCreate) {
                $row = AdvanceReceipt::create($data);
                $this->editingId = $row->id;
                $this->receipt_no = $row->fresh()->receipt_no;
            } else {
                $row = AdvanceReceipt::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Advance receipt '.$receipt->fresh()->receipt_no.($isCreate ? ' recorded.' : ' updated.'), variant: 'success');

        return redirect()->route('advance-receipt.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncAttachments(AdvanceReceipt $receipt, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('advance-receipts/'.$receipt->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = ChildRows::upsert($receipt->attachments(), $row['id'] ?? null,
                [
                    'attachment_type' => $row['attachment_type'] ?: null,
                    'kind' => $kind,
                    'path' => $path,
                    'original_name' => $originalName,
                    'size_bytes' => $size,
                    'notes' => $row['notes'] ?: null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $receipt->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('advance-receipt::edit');
    }
}
