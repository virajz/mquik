<?php

namespace App\Modules\ServiceRecommendationFollowUp\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\ServiceRecommendationFollowUp\Models\ServiceRecommendationFollowUp;
use App\Modules\ServiceRecommendationFollowUp\Models\ServiceRecommendationFollowUpAttachment;
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
#[Title('Service Recommendation Follow-Ups')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $recommendation_no = null;

    public ?int $workshop_department_id = null;

    public ?int $follow_up_by_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?string $invoice_reference = null;

    public ?string $service_history_reference = null;

    public ?string $estimate_reference = null;

    public ?string $price_list_reference = null;

    public ?string $recommended_service = null;

    public ?string $recommendation_type = null;

    public ?string $recommendation_reason = null;

    public ?string $recommendation_category = null;

    public string $priority = 'normal';

    public string $status = ServiceRecommendationFollowUp::STATUS_PENDING;

    public ?string $reminder_frequency = null;

    public ?string $follow_up_attempt = null;

    public ?string $follow_up_mode = null;

    public ?string $customer_response = null;

    public ?string $customer_satisfaction = null;

    public ?string $customer_retention = 'active';

    public ?string $escalation = null;

    public ?string $escalation_reason = null;

    public ?string $lost_reason = null;

    public ?float $estimated_value = null;

    public ?string $notes = null;

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?ServiceRecommendationFollowUp $serviceRecommendationFollowUp = null): void
    {
        if ($serviceRecommendationFollowUp && $serviceRecommendationFollowUp->exists) {
            $this->load($serviceRecommendationFollowUp);
        }
    }

    protected function load(ServiceRecommendationFollowUp $r): void
    {
        $r->load('attachments');
        $this->editingId = $r->id;
        foreach ([
            'recommendation_no', 'workshop_department_id', 'follow_up_by_id', 'customer_id', 'customer_vehicle_id',
            'invoice_reference', 'service_history_reference', 'estimate_reference', 'price_list_reference',
            'recommended_service', 'recommendation_type', 'recommendation_reason', 'recommendation_category',
            'priority', 'status', 'reminder_frequency', 'follow_up_attempt', 'follow_up_mode', 'customer_response',
            'customer_satisfaction', 'customer_retention', 'escalation', 'escalation_reason', 'lost_reason', 'notes',
        ] as $k) {
            $this->{$k} = $r->{$k};
        }
        $this->estimated_value = $r->estimated_value === null ? null : (float) $r->estimated_value;

        $this->attachments = $r->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'follow_up_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'invoice_reference' => ['nullable', 'string', 'max:255'],
            'service_history_reference' => ['nullable', 'string', 'max:255'],
            'estimate_reference' => ['nullable', 'string', 'max:255'],
            'price_list_reference' => ['nullable', 'string', 'max:255'],
            'recommended_service' => ['nullable', 'string', 'max:255'],
            'recommendation_type' => ['nullable', Rule::in(array_keys(ServiceRecommendationFollowUp::recommendationTypes()))],
            'recommendation_reason' => ['nullable', Rule::in(array_keys(ServiceRecommendationFollowUp::recommendationReasons()))],
            'recommendation_category' => ['nullable', Rule::in(array_keys(ServiceRecommendationFollowUp::recommendationCategories()))],
            'priority' => ['required', Rule::in(array_keys(ServiceRecommendationFollowUp::priorities()))],
            'status' => ['required', Rule::in(array_keys(ServiceRecommendationFollowUp::statuses()))],
            'reminder_frequency' => ['nullable', Rule::in(array_keys(ServiceRecommendationFollowUp::reminderFrequencies()))],
            'follow_up_attempt' => ['nullable', Rule::in(array_keys(ServiceRecommendationFollowUp::followUpAttempts()))],
            'follow_up_mode' => ['nullable', Rule::in(array_keys(ServiceRecommendationFollowUp::followUpModes()))],
            'customer_response' => ['nullable', Rule::in(array_keys(ServiceRecommendationFollowUp::customerResponses()))],
            'customer_satisfaction' => ['nullable', Rule::in(array_keys(ServiceRecommendationFollowUp::satisfactions()))],
            'customer_retention' => ['nullable', Rule::in(array_keys(ServiceRecommendationFollowUp::retentions()))],
            'escalation' => ['nullable', Rule::in(array_keys(ServiceRecommendationFollowUp::escalations()))],
            'escalation_reason' => ['nullable', Rule::in(array_keys(ServiceRecommendationFollowUp::escalationReasons())), Rule::requiredIf(fn () => filled($this->escalation))],
            'lost_reason' => ['nullable', Rule::in(array_keys(ServiceRecommendationFollowUp::lostReasons())), Rule::requiredIf(fn () => $this->status === ServiceRecommendationFollowUp::STATUS_LOST)],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(ServiceRecommendationFollowUpAttachment::attachmentTypes()))],
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

    // ---- Pickers -----------------------------------------------------------

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

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'service_recommendation_follow_up.update' : 'service_recommendation_follow_up.create');

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);

        foreach (['recommended_service', 'invoice_reference', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $followUp = DB::transaction(function () use ($data, $attachments, $isCreate) {
            $existing = $isCreate ? null : ServiceRecommendationFollowUp::findOrFail($this->editingId);
            $data = $this->applyTimestamps($data, $existing);

            if ($isCreate) {
                $data['recommended_at'] = now();
                $row = ServiceRecommendationFollowUp::create($data);
                $this->editingId = $row->id;
                $this->recommendation_no = $row->fresh()->recommendation_no;
            } else {
                $existing->update($data);
                $row = $existing;
            }

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Recommendation '.$followUp->fresh()->recommendation_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('service-recommendation-follow-up.index');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyTimestamps(array $data, ?ServiceRecommendationFollowUp $existing): array
    {
        if (($data['status'] ?? null) === ServiceRecommendationFollowUp::STATUS_INFORMED && ($existing?->informed_at === null)) {
            $data['informed_at'] = now();
        }
        if (($data['status'] ?? null) === ServiceRecommendationFollowUp::STATUS_QUOTATION_SENT && ($existing?->estimate_at === null)) {
            $data['estimate_at'] = now();
        }
        if (($data['status'] ?? null) === ServiceRecommendationFollowUp::STATUS_APPOINTMENT_BOOKED && ($existing?->appointment_at === null)) {
            $data['appointment_at'] = now();
        }

        return $data;
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(ServiceRecommendationFollowUp $followUp, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('service-recommendation-follow-ups/'.$followUp->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = ChildRows::upsert($followUp->attachments(), $row['id'] ?? null,
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $followUp->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('service-recommendation-follow-up::edit');
    }
}
