<?php

namespace App\Modules\OutsideLabourInquiry\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\EstimateRevisionReasonMaster\Models\EstimateRevisionReasonMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\OutsideLabourInquiry\Models\OutsideLabourInquiry;
use App\Modules\OutsideLabourRejectionReasonMaster\Models\OutsideLabourRejectionReasonMaster;
use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\ServiceSpecialistMaster\Models\ServiceSpecialistMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
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
#[Title('Outside Labour Inquiry')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $inquiry_no = null;

    public ?int $inquiry_type_id = null;

    public ?int $workshop_department_id = null;

    public ?int $employee_id = null;

    public ?int $job_card_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $vendor_id = null;

    public ?int $hsn_id = null;

    public ?int $tax_id = null;

    public ?int $priority_id = null;

    public ?string $communication_mode = null;

    public ?string $tat_option = null;

    public ?int $tat_custom_days = null;

    public ?string $promised_from = null;

    public ?string $promised_from_time = null;

    public ?string $promised_to = null;

    public ?string $promised_to_time = null;

    public ?int $revision_reason_id = null;

    public ?int $rejection_reason_id = null;

    public string $status = OutsideLabourInquiry::STATUS_RESPONSE_PENDING;

    public ?string $reminder_frequency = null;

    public ?int $reminder_custom_days = null;

    public ?int $follow_up_mode_id = null;

    public ?string $notification_stage = null;

    public ?string $notes = null;

    /** Search terms for the server-side pickers. */
    public string $vendorSearch = '';

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    public string $jobCardSearch = '';

    public string $hsnSearch = '';

    /** @var array<int, array{id:?int, labour_id:?int, job_description_id:?int, complaint_type_id:?int, description:string}> */
    public array $scopes = [];

    /** @var array<int, array{id:?int, photo_type_id:?int, kind:string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    /** Freshly uploaded files keyed by attachment row index. */
    public array $attachmentFiles = [];

    public function mount(?OutsideLabourInquiry $outsideLabourInquiry = null): void
    {
        if ($outsideLabourInquiry && $outsideLabourInquiry->exists) {
            $this->load($outsideLabourInquiry);

            return;
        }

        $this->scopes = [$this->blankScope()];
    }

    protected function load(OutsideLabourInquiry $inquiry): void
    {
        $inquiry->load(['scopes', 'attachments']);

        $this->editingId = $inquiry->id;
        foreach ([
            'inquiry_no', 'inquiry_type_id', 'workshop_department_id', 'employee_id', 'job_card_id',
            'customer_id', 'customer_vehicle_id', 'vendor_id', 'hsn_id', 'tax_id', 'priority_id',
            'communication_mode', 'tat_option', 'tat_custom_days', 'revision_reason_id',
            'rejection_reason_id', 'status', 'reminder_frequency', 'reminder_custom_days',
            'follow_up_mode_id', 'notification_stage', 'notes',
        ] as $k) {
            $this->{$k} = $inquiry->{$k};
        }
        $this->promised_from = $inquiry->promised_from?->format('Y-m-d');
        $this->promised_from_time = $inquiry->promised_from?->format('H:i');
        $this->promised_to = $inquiry->promised_to?->format('Y-m-d');
        $this->promised_to_time = $inquiry->promised_to?->format('H:i');

        $this->scopes = $inquiry->scopes->map(fn ($s) => [
            'id' => $s->id,
            'labour_id' => $s->labour_id,
            'job_description_id' => $s->job_description_id,
            'complaint_type_id' => $s->complaint_type_id,
            'description' => $s->description,
        ])->all();

        if (empty($this->scopes)) {
            $this->scopes = [$this->blankScope()];
        }

        $this->attachments = $inquiry->attachments->map(fn ($a) => [
            'id' => $a->id,
            'photo_type_id' => $a->photo_type_id,
            'kind' => $a->kind,
            'path' => $a->path,
            'original_name' => $a->original_name,
            'notes' => $a->notes,
        ])->all();
    }

    /** @return array{id:null, labour_id:null, job_description_id:null, complaint_type_id:null, description:string} */
    protected function blankScope(): array
    {
        return ['id' => null, 'labour_id' => null, 'job_description_id' => null, 'complaint_type_id' => null, 'description' => ''];
    }

    protected function rules(): array
    {
        return [
            'inquiry_type_id' => ['required', 'integer', Rule::exists('service_specialists', 'id')],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'vendor_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')],
            'hsn_id' => ['nullable', 'integer', Rule::exists('hsn_codes', 'id')],
            'tax_id' => ['nullable', 'integer', Rule::exists('taxes', 'id')],
            'priority_id' => ['nullable', 'integer', Rule::exists('priorities', 'id')],
            'communication_mode' => ['nullable', Rule::in(array_keys(OutsideLabourInquiry::communicationModes()))],
            'tat_option' => ['nullable', Rule::in(array_keys(OutsideLabourInquiry::tatOptions()))],
            'tat_custom_days' => ['nullable', 'integer', 'min:1', 'max:365', Rule::requiredIf(fn () => $this->tat_option === 'custom')],
            'promised_from' => ['nullable', 'date'],
            'promised_from_time' => ['nullable', 'string'],
            'promised_to' => ['nullable', 'date', 'after_or_equal:promised_from'],
            'promised_to_time' => ['nullable', 'string'],
            'revision_reason_id' => ['nullable', 'integer', Rule::exists('estimate_revision_reasons', 'id')],
            'rejection_reason_id' => ['nullable', 'integer', Rule::exists('outside_labour_rejection_reasons', 'id'), Rule::requiredIf(fn () => $this->status === OutsideLabourInquiry::STATUS_REJECTED)],
            'status' => ['required', Rule::in(array_keys(OutsideLabourInquiry::statuses()))],
            'reminder_frequency' => ['nullable', Rule::in(array_keys(OutsideLabourInquiry::reminderFrequencies()))],
            'reminder_custom_days' => ['nullable', 'integer', 'min:1', 'max:90', Rule::requiredIf(fn () => $this->reminder_frequency === 'custom')],
            'follow_up_mode_id' => ['nullable', 'integer', Rule::exists('follow_up_modes', 'id')],
            'notification_stage' => ['nullable', Rule::in(array_keys(OutsideLabourInquiry::notificationStages()))],
            'notes' => ['nullable', 'string', 'max:2000'],

            'scopes' => ['array'],
            'scopes.*.labour_id' => ['nullable', 'integer', Rule::exists('labours', 'id')],
            'scopes.*.job_description_id' => ['nullable', 'integer', Rule::exists('job_descriptions', 'id')],
            'scopes.*.complaint_type_id' => ['nullable', 'integer', Rule::exists('complaint_types', 'id')],
            'scopes.*.description' => ['required', 'string', 'max:500'],

            'attachments' => ['array'],
            'attachments.*.photo_type_id' => ['nullable', 'integer', Rule::exists('photo_types', 'id')],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ];
    }

    public function addScope(): void
    {
        $this->scopes[] = $this->blankScope();
    }

    public function removeScope(int $index): void
    {
        unset($this->scopes[$index]);
        $this->scopes = array_values($this->scopes);
        if (empty($this->scopes)) {
            $this->scopes = [$this->blankScope()];
        }
    }

    public function addAttachment(): void
    {
        $this->attachments[] = ['id' => null, 'photo_type_id' => null, 'kind' => 'image', 'path' => null, 'original_name' => null, 'notes' => null];
    }

    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index], $this->attachmentFiles[$index]);
        $this->attachments = array_values($this->attachments);
    }

    // ---- Pickers -----------------------------------------------------------

    #[Computed]
    public function inquiryTypes()
    {
        return ServiceSpecialistMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
            searchColumns: ['name', 'code'],
            term: $this->vendorSearch,
            selected: $this->vendor_id,
            columns: ['id', 'name'],
            limit: 30,
        );
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
    public function hsnCodes()
    {
        return $this->pickerOptions(
            query: HsnMaster::query()->where('is_active', true)->orderBy('code'),
            searchColumns: ['code'],
            term: $this->hsnSearch,
            selected: $this->hsn_id,
            columns: ['id', 'code', 'gst_percent'],
            limit: 30,
        );
    }

    #[Computed]
    public function taxes()
    {
        return TaxMaster::query()->where('is_active', true)->orderBy('gst_percent')->get(['id', 'name', 'gst_percent']);
    }

    #[Computed]
    public function priorities()
    {
        return PriorityMaster::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']);
    }

    #[Computed]
    public function followUpModes()
    {
        return FollowUpModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function revisionReasons()
    {
        return EstimateRevisionReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function rejectionReasons()
    {
        return OutsideLabourRejectionReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function labours()
    {
        return LabourMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function jobDescriptions()
    {
        return JobDescriptionMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function complaintTypes()
    {
        return ComplaintTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function photoTypes()
    {
        return PhotoTypeMaster::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'group']);
    }

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'outside_labour_inquiry.update' : 'outside_labour_inquiry.create');

        // Drop blank scope rows so an empty trailing row doesn't fail validation.
        $this->scopes = array_values(array_filter(
            $this->scopes,
            fn ($s) => filled($s['description'] ?? null) || filled($s['labour_id'] ?? null) || filled($s['job_description_id'] ?? null) || filled($s['complaint_type_id'] ?? null),
        ));

        $data = $this->validate();
        $scopes = $data['scopes'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['scopes'], $data['attachments'], $data['attachmentFiles']);

        foreach (['promised_from', 'promised_to'] as $dtField) {
            if (! empty($data[$dtField])) {
                $data[$dtField] = trim($data[$dtField].' '.($this->{$dtField.'_time'} ?: '00:00'));
            }
            unset($data[$dtField.'_time']);
        }

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }
        if ($data['tat_option'] !== 'custom') {
            $data['tat_custom_days'] = null;
        }
        if ($data['reminder_frequency'] !== 'custom') {
            $data['reminder_custom_days'] = null;
        }

        $isCreate = $this->editingId === null;

        $inquiry = DB::transaction(function () use ($data, $scopes, $attachments, $isCreate) {
            if ($isCreate) {
                $row = OutsideLabourInquiry::create($data);
                $this->editingId = $row->id;
                $this->inquiry_no = $row->fresh()->inquiry_no;
            } else {
                $row = OutsideLabourInquiry::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncScopes($row, $scopes);
            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(
            text: 'Inquiry '.$inquiry->fresh()->inquiry_no.($isCreate ? ' created.' : ' updated.'),
            variant: 'success',
        );

        return redirect()->route('outside-labour-inquiry.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncScopes(OutsideLabourInquiry $inquiry, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = $inquiry->scopes()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'labour_id' => $row['labour_id'] ?: null,
                    'job_description_id' => $row['job_description_id'] ?: null,
                    'complaint_type_id' => $row['complaint_type_id'] ?: null,
                    'description' => strtoupper(trim((string) $row['description'])),
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $inquiry->scopes()->whereKeyNot($keptIds)->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncAttachments(OutsideLabourInquiry $inquiry, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('outside-labour-inquiries/'.$inquiry->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            // A row with no file is not worth persisting.
            if ($path === null) {
                continue;
            }

            $keptIds[] = $inquiry->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'photo_type_id' => $row['photo_type_id'] ?: null,
                    'kind' => $kind,
                    'path' => $path,
                    'original_name' => $originalName,
                    'size_bytes' => $size,
                    'notes' => $row['notes'] ?: null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $inquiry->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function updatedJobCardId(): void
    {
        $this->prefillFromJobCard();
    }

    protected function prefillFromJobCard(): void
    {
        if (! $this->job_card_id) {
            return;
        }

        $jobCard = JobCard::find($this->job_card_id);

        if (! $jobCard) {
            return;
        }

        $this->workshop_department_id = $jobCard->workshop_department_id;
        $this->customer_id = $jobCard->customer_id;
        $this->customer_vehicle_id = $jobCard->customer_vehicle_id;
    }

    public function render()
    {
        return view('outside-labour-inquiry::edit');
    }
}
