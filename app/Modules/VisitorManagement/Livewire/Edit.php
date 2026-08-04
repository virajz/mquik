<?php

namespace App\Modules\VisitorManagement\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\VisitorManagement\Models\VisitorVisit;
use App\Modules\VisitorManagement\Models\VisitorVisitAttachment;
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
#[Title('Visitor Management (VMS)')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $token_no = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $department_id = null;

    public ?int $assigned_by_id = null;

    public ?int $assigned_to_id = null;

    public ?string $customer_type = null;

    public ?string $visit_purpose = null;

    public ?string $arrival_mode = null;

    public ?string $advisor_assignment_method = null;

    public ?string $advisor_availability = null;

    public ?string $waiting_time_category = null;

    public string $status = VisitorVisit::STATUS_ADVISOR_ASSIGNED;

    public ?string $delay_reason = null;

    public ?string $no_show_reason = null;

    public ?string $announcement_message = null;

    public ?string $notes = null;

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?VisitorVisit $visitorVisit = null): void
    {
        if ($visitorVisit && $visitorVisit->exists) {
            $this->load($visitorVisit);

            return;
        }

        $this->arrival_mode = 'walk_in';
        $this->advisor_assignment_method = 'auto';
    }

    protected function load(VisitorVisit $v): void
    {
        $v->load('attachments');
        $this->editingId = $v->id;
        foreach ([
            'token_no', 'customer_id', 'customer_vehicle_id', 'department_id', 'assigned_by_id', 'assigned_to_id',
            'customer_type', 'visit_purpose', 'arrival_mode', 'advisor_assignment_method', 'advisor_availability',
            'waiting_time_category', 'status', 'delay_reason', 'no_show_reason', 'announcement_message', 'notes',
        ] as $k) {
            $this->{$k} = $v->{$k};
        }

        $this->attachments = $v->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'assigned_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'assigned_to_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'customer_type' => ['nullable', Rule::in(array_keys(VisitorVisit::customerTypes()))],
            'visit_purpose' => ['nullable', Rule::in(array_keys(VisitorVisit::visitPurposes()))],
            'arrival_mode' => ['nullable', Rule::in(array_keys(VisitorVisit::arrivalModes()))],
            'advisor_assignment_method' => ['nullable', Rule::in(array_keys(VisitorVisit::advisorAssignmentMethods()))],
            'advisor_availability' => ['nullable', Rule::in(array_keys(VisitorVisit::advisorAvailabilities()))],
            'waiting_time_category' => ['nullable', Rule::in(array_keys(VisitorVisit::waitingTimeCategories()))],
            'status' => ['required', Rule::in(array_keys(VisitorVisit::statuses()))],
            'delay_reason' => ['nullable', Rule::in(array_keys(VisitorVisit::delayReasons()))],
            'no_show_reason' => ['nullable', Rule::in(array_keys(VisitorVisit::noShowReasons())), Rule::requiredIf(fn () => $this->status === VisitorVisit::STATUS_CANCELLED)],
            'announcement_message' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(VisitorVisitAttachment::attachmentTypes()))],
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
    public function departments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
        $this->authorize($this->editingId ? 'visitor_management.update' : 'visitor_management.create');

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);

        foreach (['announcement_message', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $visit = DB::transaction(function () use ($data, $attachments, $isCreate) {
            $existing = $isCreate ? null : VisitorVisit::findOrFail($this->editingId);
            $data = $this->applyTimestamps($data, $existing);

            if ($isCreate) {
                $row = VisitorVisit::create($data);
                $this->editingId = $row->id;
                $this->token_no = $row->fresh()->token_no;
            } else {
                $existing->update($data);
                $row = $existing;
            }

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Visit '.$visit->fresh()->token_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('visitor-management.index');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyTimestamps(array $data, ?VisitorVisit $existing): array
    {
        if ($existing?->arrival_at === null) {
            $data['arrival_at'] = now();
        }
        if (filled($data['assigned_to_id'] ?? null) && ($existing?->advisor_assigned_at === null)) {
            $data['advisor_assigned_at'] = now();
        }
        if (($data['status'] ?? null) === VisitorVisit::STATUS_CONSULTATION_STARTED && ($existing?->consultation_started_at === null)) {
            $data['consultation_started_at'] = now();
        }
        if (($data['status'] ?? null) === VisitorVisit::STATUS_CONSULTATION_COMPLETED && ($existing?->consultation_ended_at === null)) {
            $data['consultation_ended_at'] = now();
        }
        if (($data['status'] ?? null) === VisitorVisit::STATUS_JOB_CARD_CREATED && ($existing?->job_card_created_at === null)) {
            $data['job_card_created_at'] = now();
        }
        if (in_array($data['status'] ?? null, [VisitorVisit::STATUS_JOB_CARD_CREATED, VisitorVisit::STATUS_CANCELLED], true) && ($existing?->exit_at === null)) {
            $data['exit_at'] = now();
        }

        return $data;
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(VisitorVisit $visit, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('visitor-visits/'.$visit->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = ChildRows::upsert($visit->attachments(), $row['id'] ?? null,
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $visit->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('visitor-management::edit');
    }
}
