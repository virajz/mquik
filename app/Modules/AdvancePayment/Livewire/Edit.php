<?php

namespace App\Modules\AdvancePayment\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\AdvancePayment\Models\AdvancePayment;
use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\ChequeBounceReasonMaster\Models\ChequeBounceReasonMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\VendorAdvanceRequest\Models\VendorAdvanceRequest;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
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
#[Title('Advance Payment Entry')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $payment_no = null;

    public ?int $vendor_advance_request_id = null;

    public ?int $vendor_purchase_inquiry_id = null;

    public ?int $job_card_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $workshop_department_id = null;

    public ?int $service_type_id = null;

    public ?int $vendor_id = null;

    public ?int $entry_by_id = null;

    public ?int $store_incharge_id = null;

    public ?int $advisor_id = null;

    public ?int $payment_mode_id = null;

    public ?int $bank_id = null;

    public ?int $cheque_bounce_reason_id = null;

    public ?string $advance_payment_type = null;

    public ?float $amount = null;

    public ?string $reference_no = null;

    public ?string $cheque_no = null;

    public ?string $cheque_date = null;

    public ?string $cheque_status = null;

    public string $payment_status = AdvancePayment::STATUS_POSTED;

    public ?string $reversal_reason = null;

    public ?string $cancellation_reason = null;

    public ?string $paid_at = null;

    public ?string $notes = null;

    public string $vendorSearch = '';

    public string $requestSearch = '';

    public string $inquirySearch = '';

    public string $jobCardSearch = '';

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?AdvancePayment $advancePayment = null): void
    {
        if ($advancePayment && $advancePayment->exists) {
            $this->load($advancePayment);

            return;
        }

        $this->paid_at = now()->format('Y-m-d');
        $this->advance_payment_type = 'against_request';
    }

    protected function load(AdvancePayment $p): void
    {
        $p->load('attachments');
        $this->editingId = $p->id;
        foreach ([
            'payment_no', 'vendor_advance_request_id', 'vendor_purchase_inquiry_id', 'job_card_id', 'customer_id',
            'customer_vehicle_id', 'workshop_department_id', 'service_type_id', 'vendor_id', 'entry_by_id',
            'store_incharge_id', 'advisor_id', 'payment_mode_id', 'bank_id', 'cheque_bounce_reason_id',
            'advance_payment_type', 'reference_no', 'cheque_no', 'cheque_status', 'payment_status',
            'reversal_reason', 'cancellation_reason', 'notes',
        ] as $k) {
            $this->{$k} = $p->{$k};
        }
        $this->amount = $p->amount === null ? null : (float) $p->amount;
        $this->cheque_date = $p->cheque_date?->format('Y-m-d');
        $this->paid_at = $p->paid_at?->format('Y-m-d');

        $this->attachments = $p->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'vendor_advance_request_id' => ['nullable', 'integer', Rule::exists('vendor_advance_requests', 'id')],
            'vendor_purchase_inquiry_id' => ['nullable', 'integer', Rule::exists('vendor_purchase_inquiries', 'id')],
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')],
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'entry_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'store_incharge_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'advisor_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'payment_mode_id' => ['required', 'integer', Rule::exists('payment_modes', 'id')],
            'bank_id' => ['nullable', 'integer', Rule::exists('banks', 'id')],
            'cheque_bounce_reason_id' => ['nullable', 'integer', Rule::exists('cheque_bounce_reasons', 'id')],
            'advance_payment_type' => ['nullable', Rule::in(array_keys(AdvancePayment::advancePaymentTypes()))],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'cheque_no' => ['nullable', 'string', 'max:50'],
            'cheque_date' => ['nullable', 'date'],
            'cheque_status' => ['nullable', Rule::in(array_keys(AdvancePayment::chequeStatuses()))],
            'payment_status' => ['required', Rule::in(array_keys(AdvancePayment::paymentStatuses()))],
            'reversal_reason' => ['nullable', Rule::in(array_keys(AdvancePayment::reversalReasons())), Rule::requiredIf(fn () => $this->payment_status === AdvancePayment::STATUS_REVERSED)],
            'cancellation_reason' => ['nullable', Rule::in(array_keys(AdvancePayment::cancellationReasons())), Rule::requiredIf(fn () => $this->payment_status === AdvancePayment::STATUS_CANCELLED)],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(AdvancePayment::attachmentTypes()))],
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
    public function vendors()
    {
        return $this->pickerOptions(
            query: VendorMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'vendor_code'], term: $this->vendorSearch, selected: $this->vendor_id, columns: ['id', 'name'], limit: 30,
        );
    }

    #[Computed]
    public function advanceRequests()
    {
        return $this->pickerOptions(
            query: VendorAdvanceRequest::query()->latest('id'),
            searchColumns: ['request_no'], term: $this->requestSearch, selected: $this->vendor_advance_request_id, columns: ['id', 'request_no'], limit: 30,
        );
    }

    #[Computed]
    public function inquiries()
    {
        return $this->pickerOptions(
            query: VendorPurchaseInquiry::query()->latest('id'),
            searchColumns: ['vpi_no'], term: $this->inquirySearch, selected: $this->vendor_purchase_inquiry_id, columns: ['id', 'vpi_no'], limit: 30,
        );
    }

    #[Computed]
    public function jobCards()
    {
        return $this->pickerOptions(
            query: JobCard::query()->latest('id'),
            searchColumns: ['job_card_no'], term: $this->jobCardSearch, selected: $this->job_card_id, columns: ['id', 'job_card_no'], limit: 30,
        );
    }

    #[Computed]
    public function customers()
    {
        return $this->pickerOptions(
            query: CustomerMaster::query()->where('is_active', true)->orderBy('first_name'),
            searchColumns: ['first_name', 'last_name', 'phone'], term: $this->customerSearch, selected: $this->customer_id, columns: ['id', 'first_name', 'last_name'], limit: 30,
        );
    }

    #[Computed]
    public function vehicles()
    {
        return $this->pickerOptions(
            query: CustomerVehicleMaster::query()->orderBy('registration_no'),
            searchColumns: ['registration_no'], term: $this->vehicleSearch, selected: $this->customer_vehicle_id, columns: ['id', 'registration_no'], limit: 30,
        );
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'advance_payment.update' : 'advance_payment.create');

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);

        foreach (['reference_no', 'cheque_no', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $payment = DB::transaction(function () use ($data, $attachments, $isCreate) {
            if ($isCreate) {
                $row = AdvancePayment::create($data);
                $this->editingId = $row->id;
                $this->payment_no = $row->fresh()->payment_no;
            } else {
                $row = AdvancePayment::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Advance payment '.$payment->fresh()->payment_no.($isCreate ? ' recorded.' : ' updated.'), variant: 'success');

        return redirect()->route('advance-payment.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(AdvancePayment $payment, array $rows): void
    {
        $keptIds = [];
        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';
            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('advance-payments/'.$payment->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }
            if ($path === null) {
                continue;
            }
            $keptIds[] = $payment->attachments()->updateOrCreate(['id' => $row['id'] ?? null], [
                'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
            ])->id;
        }
        $payment->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('advance-payment::edit');
    }
}
