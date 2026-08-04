<?php

namespace App\Modules\CustomerFeedback\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerFeedback\Models\CustomerFeedback;
use App\Modules\CustomerFeedback\Models\CustomerFeedbackAttachment;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GatePassApproval\Models\GatePassApproval;
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
#[Title('Customer Feedback')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $feedback_no = null;

    public ?string $follow_up_schedule = null;

    public ?int $follow_up_custom_days = null;

    public ?string $follow_up_category = null;

    public ?string $follow_up_mode = null;

    public ?string $follow_up_attempt = null;

    public ?string $vehicle_observation = null;

    public ?string $feedback_source = null;

    public ?string $feedback_category = null;

    public string $status = CustomerFeedback::STATUS_SENT;

    public ?int $workshop_department_id = null;

    public ?int $service_type_id = null;

    public ?int $advisor_id = null;

    public ?int $technician_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $gate_pass_approval_id = null;

    public ?string $invoice_reference = null;

    public ?int $staff_experience_rating = null;

    public ?int $service_experience_rating = null;

    public ?int $service_rating = null;

    public ?int $price_rating = null;

    public ?int $ontime_delivery_rating = null;

    public ?bool $would_recommend = null;

    public ?string $notes = null;

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    public string $gatePassSearch = '';

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?CustomerFeedback $customerFeedback = null): void
    {
        if ($customerFeedback && $customerFeedback->exists) {
            $this->load($customerFeedback);
        }
    }

    protected function load(CustomerFeedback $f): void
    {
        $f->load('attachments');
        $this->editingId = $f->id;
        foreach ([
            'feedback_no', 'follow_up_schedule', 'follow_up_custom_days', 'follow_up_category', 'follow_up_mode',
            'follow_up_attempt', 'vehicle_observation', 'feedback_source', 'feedback_category', 'status',
            'workshop_department_id', 'service_type_id', 'advisor_id', 'technician_id', 'customer_id',
            'customer_vehicle_id', 'gate_pass_approval_id', 'invoice_reference', 'staff_experience_rating',
            'service_experience_rating', 'service_rating', 'price_rating', 'ontime_delivery_rating',
            'would_recommend', 'notes',
        ] as $k) {
            $this->{$k} = $f->{$k};
        }

        $this->attachments = $f->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    protected function rules(): array
    {
        $rating = ['nullable', 'integer', 'between:1,5'];

        return [
            'follow_up_schedule' => ['nullable', Rule::in(array_keys(CustomerFeedback::followUpSchedules()))],
            'follow_up_custom_days' => ['nullable', 'integer', 'min:1', 'max:365', Rule::requiredIf(fn () => $this->follow_up_schedule === 'custom')],
            'follow_up_category' => ['nullable', Rule::in(array_keys(CustomerFeedback::followUpCategories()))],
            'follow_up_mode' => ['nullable', Rule::in(array_keys(CustomerFeedback::followUpModes()))],
            'follow_up_attempt' => ['nullable', Rule::in(array_keys(CustomerFeedback::followUpAttempts()))],
            'vehicle_observation' => ['nullable', Rule::in(array_keys(CustomerFeedback::vehicleObservations()))],
            'feedback_source' => ['nullable', Rule::in(array_keys(CustomerFeedback::feedbackSources()))],
            'feedback_category' => ['nullable', Rule::in(array_keys(CustomerFeedback::feedbackCategories()))],
            'status' => ['required', Rule::in(array_keys(CustomerFeedback::statuses()))],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')],
            'advisor_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'technician_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'gate_pass_approval_id' => ['nullable', 'integer', Rule::exists('gate_pass_approvals', 'id')],
            'invoice_reference' => ['nullable', 'string', 'max:255'],
            'staff_experience_rating' => $rating,
            'service_experience_rating' => $rating,
            'service_rating' => $rating,
            'price_rating' => $rating,
            'ontime_delivery_rating' => $rating,
            'would_recommend' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(CustomerFeedbackAttachment::attachmentTypes()))],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ];
    }

    public function addAttachment(): void
    {
        $this->attachments[] = ['id' => null, 'attachment_type' => 'feedback_screenshot', 'path' => null, 'original_name' => null, 'notes' => null];
    }

    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index], $this->attachmentFiles[$index]);
        $this->attachments = array_values($this->attachments);
    }

    // ---- Pickers -----------------------------------------------------------

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

    #[Computed]
    public function gatePasses()
    {
        return $this->pickerOptions(
            query: GatePassApproval::query()->latest('id'),
            searchColumns: ['approval_no'], term: $this->gatePassSearch, selected: $this->gate_pass_approval_id, columns: ['id', 'approval_no'], limit: 30,
        );
    }

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'customer_feedback.update' : 'customer_feedback.create');

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);

        if (isset($data['invoice_reference']) && is_string($data['invoice_reference'])) {
            $data['invoice_reference'] = strtoupper($data['invoice_reference']);
        }
        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }
        if ($data['follow_up_schedule'] !== 'custom') {
            $data['follow_up_custom_days'] = null;
        }

        $submittedStatuses = [CustomerFeedback::STATUS_SATISFIED, CustomerFeedback::STATUS_DISSATISFIED, CustomerFeedback::STATUS_RESOLVED];

        $isCreate = $this->editingId === null;

        $feedback = DB::transaction(function () use ($data, $attachments, $isCreate, $submittedStatuses) {
            if ($isCreate) {
                $data['requested_at'] = now();
                if (in_array($data['status'], $submittedStatuses, true)) {
                    $data['submitted_at'] = now();
                }
                if ($data['status'] === CustomerFeedback::STATUS_RESOLVED) {
                    $data['closed_at'] = now();
                }
                $row = CustomerFeedback::create($data);
                $this->editingId = $row->id;
                $this->feedback_no = $row->fresh()->feedback_no;
            } else {
                $row = CustomerFeedback::findOrFail($this->editingId);
                if (in_array($data['status'], $submittedStatuses, true) && $row->submitted_at === null) {
                    $data['submitted_at'] = now();
                }
                if ($data['status'] === CustomerFeedback::STATUS_RESOLVED && $row->closed_at === null) {
                    $data['closed_at'] = now();
                }
                $row->update($data);
            }

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Feedback '.$feedback->fresh()->feedback_no.($isCreate ? ' recorded.' : ' updated.'), variant: 'success');

        return redirect()->route('customer-feedback.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(CustomerFeedback $feedback, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('customer-feedbacks/'.$feedback->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = ChildRows::upsert($feedback->attachments(), $row['id'] ?? null,
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $feedback->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('customer-feedback::edit');
    }
}
