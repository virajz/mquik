<?php

namespace App\Modules\CustomerComplaint\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerComplaint\Models\CustomerComplaint;
use App\Modules\CustomerComplaint\Models\CustomerComplaintAttachment;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
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
#[Title('Customer Complaints')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $complaint_no = null;

    public ?string $complaint_type = null;

    public ?string $complaint_source = null;

    public string $priority = 'normal';

    public ?string $assignment = null;

    public ?string $root_cause = null;

    public ?string $resolution_type = null;

    public string $status = CustomerComplaint::STATUS_UNDER_INVESTIGATION;

    public ?string $reopen_reason = null;

    public ?int $workshop_department_id = null;

    public ?int $service_type_id = null;

    public ?int $opened_by_id = null;

    public ?int $advisor_id = null;

    public ?int $technician_id = null;

    public ?int $job_card_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?string $invoice_reference = null;

    public ?string $description = null;

    public ?int $achieved_score = null;

    public ?int $recommended_score = null;

    public ?string $notes = null;

    public string $jobCardSearch = '';

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?CustomerComplaint $customerComplaint = null): void
    {
        if ($customerComplaint && $customerComplaint->exists) {
            $this->load($customerComplaint);
        }
    }

    protected function load(CustomerComplaint $c): void
    {
        $c->load('attachments');
        $this->editingId = $c->id;
        foreach ([
            'complaint_no', 'complaint_type', 'complaint_source', 'priority', 'assignment', 'root_cause',
            'resolution_type', 'status', 'reopen_reason', 'workshop_department_id', 'service_type_id',
            'opened_by_id', 'advisor_id', 'technician_id', 'job_card_id', 'customer_id', 'customer_vehicle_id',
            'invoice_reference', 'description', 'achieved_score', 'recommended_score', 'notes',
        ] as $k) {
            $this->{$k} = $c->{$k};
        }

        $this->attachments = $c->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'complaint_type' => ['nullable', Rule::in(array_keys(CustomerComplaint::complaintTypes()))],
            'complaint_source' => ['nullable', Rule::in(array_keys(CustomerComplaint::complaintSources()))],
            'priority' => ['required', Rule::in(array_keys(CustomerComplaint::priorities()))],
            'assignment' => ['nullable', Rule::in(array_keys(CustomerComplaint::assignments()))],
            'root_cause' => ['nullable', Rule::in(array_keys(CustomerComplaint::rootCauses()))],
            'resolution_type' => ['nullable', Rule::in(array_keys(CustomerComplaint::resolutionTypes()))],
            'status' => ['required', Rule::in(array_keys(CustomerComplaint::statuses()))],
            'reopen_reason' => ['nullable', Rule::in(array_keys(CustomerComplaint::reopenReasons()))],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')],
            'opened_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'advisor_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'technician_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'invoice_reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'achieved_score' => ['nullable', 'integer', 'between:1,5', Rule::requiredIf(fn () => $this->status === CustomerComplaint::STATUS_RESOLVED)],
            'recommended_score' => ['nullable', 'integer', 'between:1,5'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(CustomerComplaintAttachment::attachmentTypes()))],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,mp4,mov,webm,mp3,m4a,ogg', 'max:51200'],
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

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'customer_complaint.update' : 'customer_complaint.create');

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);

        foreach (['invoice_reference', 'description', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $complaint = DB::transaction(function () use ($data, $attachments, $isCreate) {
            if ($isCreate) {
                $data['opened_at'] = now();
                if (filled($data['assignment'] ?? null)) {
                    $data['assigned_at'] = now();
                }
                if ($data['status'] === CustomerComplaint::STATUS_RESOLVED) {
                    $data['closed_at'] = now();
                }
                $row = CustomerComplaint::create($data);
                $this->editingId = $row->id;
                $this->complaint_no = $row->fresh()->complaint_no;
            } else {
                $row = CustomerComplaint::findOrFail($this->editingId);
                if (filled($data['assignment'] ?? null) && $row->assigned_at === null) {
                    $data['assigned_at'] = now();
                }
                if ($data['status'] === CustomerComplaint::STATUS_RESOLVED && $row->closed_at === null) {
                    $data['closed_at'] = now();
                }
                if (filled($data['reopen_reason'] ?? null) && $row->reopened_at === null) {
                    $data['reopened_at'] = now();
                }
                $row->update($data);
            }

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Complaint '.$complaint->fresh()->complaint_no.($isCreate ? ' registered.' : ' updated.'), variant: 'success');

        return redirect()->route('customer-complaint.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(CustomerComplaint $complaint, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('customer-complaints/'.$complaint->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = $this->kindForExtension(strtolower((string) $upload->getClientOriginalExtension()));
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = ChildRows::upsert($complaint->attachments(), $row['id'] ?? null,
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $complaint->attachments()->whereKeyNot($keptIds)->delete();
    }

    protected function kindForExtension(string $ext): string
    {
        return match (true) {
            $ext === 'pdf' => 'pdf',
            in_array($ext, ['mp4', 'mov', 'webm'], true) => 'video',
            in_array($ext, ['mp3', 'm4a', 'ogg'], true) => 'audio',
            default => 'image',
        };
    }

    public function render()
    {
        return view('customer-complaint::edit');
    }
}
