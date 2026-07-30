<?php

namespace App\Modules\ServiceDueFollowUp\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\ServiceDueFollowUp\Models\ServiceDueFollowUp;
use App\Modules\ServiceDueFollowUp\Models\ServiceDueFollowUpAttachment;
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
#[Title('Service Due Follow-Ups')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $follow_up_no = null;

    public ?int $follow_up_by_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?string $service_history_reference = null;

    public ?string $estimate_template_reference = null;

    public ?string $price_list_reference = null;

    public ?string $recommended_service_reference = null;

    public string $status = ServiceDueFollowUp::STATUS_PENDING;

    public ?string $follow_up_attempt = null;

    public ?string $follow_up_mode = null;

    public ?string $customer_response = null;

    public ?string $customer_satisfaction = null;

    public ?string $lost_reason = null;

    public ?string $escalation = null;

    public ?string $escalation_reason = null;

    public ?string $customer_retention = 'active';

    public ?string $service_interval_method = null;

    public ?string $service_interval = null;

    public ?string $reminder_frequency = null;

    public ?string $due_date = null;

    public ?int $odometer = null;

    public ?string $appointment_at = null;

    public ?string $appointment_at_time = null;

    public ?string $notes = null;

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?ServiceDueFollowUp $serviceDueFollowUp = null): void
    {
        if ($serviceDueFollowUp && $serviceDueFollowUp->exists) {
            $this->load($serviceDueFollowUp);

            return;
        }

        $this->service_interval_method = 'date';
    }

    protected function load(ServiceDueFollowUp $f): void
    {
        $f->load('attachments');
        $this->editingId = $f->id;
        foreach ([
            'follow_up_no', 'follow_up_by_id', 'customer_id', 'customer_vehicle_id', 'service_history_reference',
            'estimate_template_reference', 'price_list_reference', 'recommended_service_reference', 'status',
            'follow_up_attempt', 'follow_up_mode', 'customer_response', 'customer_satisfaction', 'lost_reason',
            'escalation', 'escalation_reason', 'customer_retention', 'service_interval_method', 'service_interval',
            'reminder_frequency', 'odometer', 'notes',
        ] as $k) {
            $this->{$k} = $f->{$k};
        }
        $this->due_date = $f->due_date?->format('Y-m-d');
        $this->appointment_at = $f->appointment_at?->format('Y-m-d');
        $this->appointment_at_time = $f->appointment_at?->format('H:i');

        $this->attachments = $f->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'follow_up_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'service_history_reference' => ['nullable', 'string', 'max:255'],
            'estimate_template_reference' => ['nullable', 'string', 'max:255'],
            'price_list_reference' => ['nullable', 'string', 'max:255'],
            'recommended_service_reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(ServiceDueFollowUp::statuses()))],
            'follow_up_attempt' => ['nullable', Rule::in(array_keys(ServiceDueFollowUp::followUpAttempts()))],
            'follow_up_mode' => ['nullable', Rule::in(array_keys(ServiceDueFollowUp::followUpModes()))],
            'customer_response' => ['nullable', Rule::in(array_keys(ServiceDueFollowUp::customerResponses()))],
            'customer_satisfaction' => ['nullable', Rule::in(array_keys(ServiceDueFollowUp::satisfactions()))],
            'lost_reason' => ['nullable', Rule::in(array_keys(ServiceDueFollowUp::lostReasons())), Rule::requiredIf(fn () => $this->status === ServiceDueFollowUp::STATUS_LOST)],
            'escalation' => ['nullable', Rule::in(array_keys(ServiceDueFollowUp::escalations()))],
            'escalation_reason' => ['nullable', Rule::in(array_keys(ServiceDueFollowUp::escalationReasons())), Rule::requiredIf(fn () => filled($this->escalation))],
            'customer_retention' => ['nullable', Rule::in(array_keys(ServiceDueFollowUp::retentions()))],
            'service_interval_method' => ['nullable', Rule::in(array_keys(ServiceDueFollowUp::intervalMethods()))],
            'service_interval' => ['nullable', 'string', 'max:20'],
            'reminder_frequency' => ['nullable', Rule::in(array_keys(ServiceDueFollowUp::reminderFrequencies()))],
            'due_date' => ['nullable', 'date'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'appointment_at' => ['nullable', 'date'],
            'appointment_at_time' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(ServiceDueFollowUpAttachment::attachmentTypes()))],
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
        $this->authorize($this->editingId ? 'service_due_follow_up.update' : 'service_due_follow_up.create');

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);

        foreach (['appointment_at'] as $dtField) {
            if (! empty($data[$dtField])) {
                $data[$dtField] = trim($data[$dtField].' '.($this->{$dtField.'_time'} ?: '00:00'));
            }
            unset($data[$dtField.'_time']);
        }

        foreach (['service_history_reference', 'estimate_template_reference', 'price_list_reference', 'recommended_service_reference', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $followUp = DB::transaction(function () use ($data, $attachments, $isCreate) {
            if ($isCreate) {
                $data['due_generated_at'] = now();
                $data = $this->applyStatusTimestamps($data, null);
                $row = ServiceDueFollowUp::create($data);
                $this->editingId = $row->id;
                $this->follow_up_no = $row->fresh()->follow_up_no;
            } else {
                $row = ServiceDueFollowUp::findOrFail($this->editingId);
                $data = $this->applyStatusTimestamps($data, $row);
                $row->update($data);
            }

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Follow-up '.$followUp->fresh()->follow_up_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('service-due-follow-up.index');
    }

    /**
     * Stamp the lifecycle timestamps as the status / fields advance.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyStatusTimestamps(array $data, ?ServiceDueFollowUp $existing): array
    {
        if (filled($data['customer_response'] ?? null) && ($existing?->response_at === null)) {
            $data['response_at'] = now();
        }
        if (($data['status'] ?? null) === ServiceDueFollowUp::STATUS_APPOINTMENT_BOOKED && ($existing?->appointment_at === null) && empty($data['appointment_at'])) {
            $data['appointment_at'] = now();
        }
        if (($data['status'] ?? null) === ServiceDueFollowUp::STATUS_CONVERTED && ($existing?->job_card_open_at === null)) {
            $data['job_card_open_at'] = now();
        }

        return $data;
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(ServiceDueFollowUp $followUp, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('service-due-follow-ups/'.$followUp->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $followUp->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
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
        return view('service-due-follow-up::edit');
    }
}
