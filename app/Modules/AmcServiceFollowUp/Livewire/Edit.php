<?php

namespace App\Modules\AmcServiceFollowUp\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\AmcServiceFollowUp\Models\AmcServiceFollowUp;
use App\Modules\AmcServiceFollowUp\Models\AmcServiceFollowUpAttachment;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\VehicleAmc\Models\VehicleAmc;
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
#[Title('AMC Service Due / Renewal Follow-Ups')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $follow_up_no = null;

    public string $follow_up_type = 'service_due';

    public ?int $vehicle_amc_id = null;

    public ?int $follow_up_by_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?string $vehicle_history_reference = null;

    public ?string $estimate_template_reference = null;

    public ?string $price_list_reference = null;

    public ?string $package_reference = null;

    public string $status = AmcServiceFollowUp::STATUS_PENDING;

    public ?string $follow_up_attempt = null;

    public ?string $follow_up_mode = null;

    public ?string $customer_response = null;

    public ?string $customer_satisfaction = null;

    public ?string $lost_reason = null;

    public ?string $missed_service_reason = null;

    public ?string $escalation = null;

    public ?string $escalation_reason = null;

    public ?string $customer_retention = 'active';

    public ?string $service_interval_method = null;

    public ?string $service_interval = null;

    public ?string $reminder_frequency = null;

    public ?string $due_date = null;

    public ?int $odometer = null;

    public ?string $appointment_at = null;

    public ?string $notes = null;

    public string $amcSearch = '';

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?AmcServiceFollowUp $amcServiceFollowUp = null): void
    {
        if ($amcServiceFollowUp && $amcServiceFollowUp->exists) {
            $this->load($amcServiceFollowUp);

            return;
        }

        $this->service_interval_method = 'date';
    }

    protected function load(AmcServiceFollowUp $f): void
    {
        $f->load('attachments');
        $this->editingId = $f->id;
        foreach ([
            'follow_up_no', 'follow_up_type', 'vehicle_amc_id', 'follow_up_by_id', 'customer_id', 'customer_vehicle_id',
            'vehicle_history_reference', 'estimate_template_reference', 'price_list_reference', 'package_reference',
            'status', 'follow_up_attempt', 'follow_up_mode', 'customer_response', 'customer_satisfaction', 'lost_reason',
            'missed_service_reason', 'escalation', 'escalation_reason', 'customer_retention', 'service_interval_method',
            'service_interval', 'reminder_frequency', 'odometer', 'notes',
        ] as $k) {
            $this->{$k} = $f->{$k};
        }
        $this->due_date = $f->due_date?->format('Y-m-d');
        $this->appointment_at = $f->appointment_at?->format('Y-m-d\TH:i');

        $this->attachments = $f->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'follow_up_type' => ['required', Rule::in(array_keys(AmcServiceFollowUp::followUpTypes()))],
            'vehicle_amc_id' => ['nullable', 'integer', Rule::exists('vehicle_amcs', 'id')],
            'follow_up_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'vehicle_history_reference' => ['nullable', 'string', 'max:255'],
            'estimate_template_reference' => ['nullable', 'string', 'max:255'],
            'price_list_reference' => ['nullable', 'string', 'max:255'],
            'package_reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(AmcServiceFollowUp::statuses()))],
            'follow_up_attempt' => ['nullable', Rule::in(array_keys(AmcServiceFollowUp::followUpAttempts()))],
            'follow_up_mode' => ['nullable', Rule::in(array_keys(AmcServiceFollowUp::followUpModes()))],
            'customer_response' => ['nullable', Rule::in(array_keys(AmcServiceFollowUp::customerResponses()))],
            'customer_satisfaction' => ['nullable', Rule::in(array_keys(AmcServiceFollowUp::satisfactions()))],
            'lost_reason' => ['nullable', Rule::in(array_keys(AmcServiceFollowUp::lostReasons())), Rule::requiredIf(fn () => $this->status === AmcServiceFollowUp::STATUS_LOST)],
            'missed_service_reason' => ['nullable', Rule::in(array_keys(AmcServiceFollowUp::missedServiceReasons()))],
            'escalation' => ['nullable', Rule::in(array_keys(AmcServiceFollowUp::escalations()))],
            'escalation_reason' => ['nullable', Rule::in(array_keys(AmcServiceFollowUp::escalationReasons())), Rule::requiredIf(fn () => filled($this->escalation))],
            'customer_retention' => ['nullable', Rule::in(array_keys(AmcServiceFollowUp::retentions()))],
            'service_interval_method' => ['nullable', Rule::in(array_keys(AmcServiceFollowUp::intervalMethods()))],
            'service_interval' => ['nullable', 'string', 'max:20'],
            'reminder_frequency' => ['nullable', Rule::in(array_keys(AmcServiceFollowUp::reminderFrequencies()))],
            'due_date' => ['nullable', 'date'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'appointment_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(AmcServiceFollowUpAttachment::attachmentTypes()))],
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
    public function amcs()
    {
        return $this->pickerOptions(
            query: VehicleAmc::query()->latest('id'),
            searchColumns: ['amc_no'], term: $this->amcSearch, selected: $this->vehicle_amc_id, columns: ['id', 'amc_no'], limit: 30,
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

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'amc_service_follow_up.update' : 'amc_service_follow_up.create');

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);

        foreach (['vehicle_history_reference', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $followUp = DB::transaction(function () use ($data, $attachments, $isCreate) {
            $existing = $isCreate ? null : AmcServiceFollowUp::findOrFail($this->editingId);
            $data = $this->applyTimestamps($data, $existing);

            if ($isCreate) {
                $data['due_generated_at'] = now();
                $row = AmcServiceFollowUp::create($data);
                $this->editingId = $row->id;
                $this->follow_up_no = $row->fresh()->follow_up_no;
            } else {
                $existing->update($data);
                $row = $existing;
            }

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Follow-up '.$followUp->fresh()->follow_up_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('amc-service-follow-up.index');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyTimestamps(array $data, ?AmcServiceFollowUp $existing): array
    {
        if (filled($data['customer_response'] ?? null) && ($existing?->response_at === null)) {
            $data['response_at'] = now();
        }
        if (($data['status'] ?? null) === AmcServiceFollowUp::STATUS_APPOINTMENT_BOOKED && ($existing?->appointment_at === null) && empty($data['appointment_at'])) {
            $data['appointment_at'] = now();
        }
        if (($data['status'] ?? null) === AmcServiceFollowUp::STATUS_CONVERTED && ($existing?->job_card_open_at === null)) {
            $data['job_card_open_at'] = now();
        }

        return $data;
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(AmcServiceFollowUp $followUp, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('amc-service-follow-ups/'.$followUp->id, 'public');
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
        return view('amc-service-follow-up::edit');
    }
}
