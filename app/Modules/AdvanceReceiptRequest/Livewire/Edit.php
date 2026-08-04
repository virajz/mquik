<?php

namespace App\Modules\AdvanceReceiptRequest\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\AdvanceReceiptRequest\Models\AdvanceReceiptRequest;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\JobCard\Models\JobCard;
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
#[Title('Advance Receipt Request')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $request_no = null;

    public ?int $job_card_id = null;

    public ?int $sales_estimate_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $workshop_department_id = null;

    public ?int $service_type_id = null;

    public ?int $employee_id = null;

    public ?int $follow_up_mode_id = null;

    public ?string $advance_purpose = null;

    public ?string $amount_type = null;

    public ?float $percent = null;

    public ?float $amount = null;

    public string $payment_status = AdvanceReceiptRequest::STATUS_REQUESTED;

    public ?string $reminder_time = null;

    public ?string $reminder_custom_time = null;

    public ?string $rejection_reason = null;

    public ?string $notes = null;

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    public string $jobCardSearch = '';

    public string $estimateSearch = '';

    /** @var array<int, array{id:?int, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?AdvanceReceiptRequest $advanceReceiptRequest = null): void
    {
        if ($advanceReceiptRequest && $advanceReceiptRequest->exists) {
            $this->load($advanceReceiptRequest);
        }
    }

    protected function load(AdvanceReceiptRequest $r): void
    {
        $r->load('attachments');
        $this->editingId = $r->id;
        foreach ([
            'request_no', 'job_card_id', 'sales_estimate_id', 'customer_id', 'customer_vehicle_id',
            'workshop_department_id', 'service_type_id', 'employee_id', 'follow_up_mode_id',
            'advance_purpose', 'amount_type', 'payment_status', 'reminder_time', 'reminder_custom_time',
            'rejection_reason', 'notes',
        ] as $k) {
            $this->{$k} = $r->{$k};
        }
        $this->percent = $r->percent === null ? null : (float) $r->percent;
        $this->amount = $r->amount === null ? null : (float) $r->amount;

        $this->attachments = $r->attachments->map(fn ($a) => [
            'id' => $a->id,
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
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'follow_up_mode_id' => ['nullable', 'integer', Rule::exists('follow_up_modes', 'id')],
            'advance_purpose' => ['nullable', Rule::in(array_keys(AdvanceReceiptRequest::advancePurposes()))],
            'amount_type' => ['nullable', Rule::in(array_keys(AdvanceReceiptRequest::amountTypes()))],
            'percent' => ['nullable', 'numeric', 'min:0', 'max:100', Rule::requiredIf(fn () => $this->amount_type === 'percent_of_estimate')],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', Rule::in(array_keys(AdvanceReceiptRequest::paymentStatuses()))],
            'reminder_time' => ['nullable', Rule::in(array_keys(AdvanceReceiptRequest::reminderTimes()))],
            'reminder_custom_time' => ['nullable', 'string', 'max:20', Rule::requiredIf(fn () => $this->reminder_time === 'custom')],
            'rejection_reason' => ['nullable', Rule::in(array_keys(AdvanceReceiptRequest::rejectionReasons())), Rule::requiredIf(fn () => $this->payment_status === AdvanceReceiptRequest::STATUS_REJECTED)],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachments' => ['array'],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ];
    }

    public function addAttachment(): void
    {
        $this->attachments[] = ['id' => null, 'path' => null, 'original_name' => null, 'notes' => null];
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
    public function followUpModes()
    {
        return FollowUpModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
        $this->authorize($this->editingId ? 'advance_receipt_request.update' : 'advance_receipt_request.create');

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }
        if ($data['amount_type'] !== 'percent_of_estimate') {
            $data['percent'] = null;
        }
        if ($data['reminder_time'] !== 'custom') {
            $data['reminder_custom_time'] = null;
        }

        $isCreate = $this->editingId === null;

        $request = DB::transaction(function () use ($data, $attachments, $isCreate) {
            if ($isCreate) {
                $row = AdvanceReceiptRequest::create($data);
                $this->editingId = $row->id;
                $this->request_no = $row->fresh()->request_no;
            } else {
                $row = AdvanceReceiptRequest::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Advance request '.$request->fresh()->request_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('advance-receipt-request.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncAttachments(AdvanceReceiptRequest $request, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('advance-receipt-requests/'.$request->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = ChildRows::upsert($request->attachments(), $row['id'] ?? null,
                [
                    'kind' => $kind,
                    'path' => $path,
                    'original_name' => $originalName,
                    'size_bytes' => $size,
                    'notes' => $row['notes'] ?: null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $request->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('advance-receipt-request::edit');
    }
}
