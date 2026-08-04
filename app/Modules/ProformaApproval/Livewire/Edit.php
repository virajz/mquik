<?php

namespace App\Modules\ProformaApproval\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\ProformaApproval\Models\ProformaApproval;
use App\Modules\ProformaApproval\Models\ProformaApprovalAttachment;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
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
#[Title('Proforma Approval')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $approval_no = null;

    public ?string $approval_stage = 'stage_1';

    public ?string $approval_authority = null;

    public string $priority = 'normal';

    public string $status = ProformaApproval::STATUS_UNDER_PREPARATION;

    public ?int $job_card_id = null;

    public ?int $sales_estimate_id = null;

    public ?int $insurance_company_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $workshop_department_id = null;

    public ?int $service_type_id = null;

    public ?int $vendor_id = null;

    public ?int $follow_up_mode_id = null;

    public ?int $billing_executive_id = null;

    public ?int $store_incharge_id = null;

    public ?int $advisor_id = null;

    public ?int $technician_id = null;

    public ?string $loss_reason = null;

    public ?string $missing_reason = null;

    public ?string $discount_type = null;

    public ?string $rejection_reason = null;

    public ?string $proforma_reference = null;

    public ?float $amount = null;

    public ?string $store_approved_at = null;

    public ?string $advisor_approved_at = null;

    public ?string $admin_approved_at = null;

    public ?string $notes = null;

    public string $jobCardSearch = '';

    public string $estimateSearch = '';

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    /** @var array<int, array{id:?int, role:string, checkpoint:?string, status:string, note:?string}> */
    public array $checkpoints = [];

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?ProformaApproval $proformaApproval = null): void
    {
        if ($proformaApproval && $proformaApproval->exists) {
            $this->load($proformaApproval);

            return;
        }

        $this->checkpoints = [$this->blankCheckpoint()];
    }

    protected function load(ProformaApproval $p): void
    {
        $p->load(['checkpoints', 'attachments']);
        $this->editingId = $p->id;
        foreach ([
            'approval_no', 'approval_stage', 'approval_authority', 'priority', 'status', 'job_card_id',
            'sales_estimate_id', 'insurance_company_id', 'customer_id', 'customer_vehicle_id',
            'workshop_department_id', 'service_type_id', 'vendor_id', 'follow_up_mode_id',
            'billing_executive_id', 'store_incharge_id', 'advisor_id', 'technician_id', 'loss_reason',
            'missing_reason', 'discount_type', 'rejection_reason', 'proforma_reference', 'notes',
        ] as $k) {
            $this->{$k} = $p->{$k};
        }
        $this->amount = $p->amount === null ? null : (float) $p->amount;
        $this->store_approved_at = $p->store_approved_at?->format('Y-m-d');
        $this->advisor_approved_at = $p->advisor_approved_at?->format('Y-m-d');
        $this->admin_approved_at = $p->admin_approved_at?->format('Y-m-d');

        $this->checkpoints = $p->checkpoints->map(fn ($c) => [
            'id' => $c->id, 'role' => $c->role, 'checkpoint' => $c->checkpoint, 'status' => $c->status, 'note' => $c->note,
        ])->all();

        if (empty($this->checkpoints)) {
            $this->checkpoints = [$this->blankCheckpoint()];
        }

        $this->attachments = $p->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    /** @return array{id:null, role:string, checkpoint:null, status:string, note:null} */
    protected function blankCheckpoint(): array
    {
        return ['id' => null, 'role' => 'billing_executive', 'checkpoint' => null, 'status' => 'ok', 'note' => null];
    }

    protected function rules(): array
    {
        return [
            'approval_stage' => ['nullable', Rule::in(array_keys(ProformaApproval::stages()))],
            'approval_authority' => ['nullable', Rule::in(array_keys(ProformaApproval::approvalAuthorities()))],
            'priority' => ['required', Rule::in(array_keys(ProformaApproval::priorities()))],
            'status' => ['required', Rule::in(array_keys(ProformaApproval::statuses()))],
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'sales_estimate_id' => ['nullable', 'integer', Rule::exists('sales_estimates', 'id')],
            'insurance_company_id' => ['nullable', 'integer', Rule::exists('insurance_companies', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')],
            'vendor_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')],
            'follow_up_mode_id' => ['nullable', 'integer', Rule::exists('follow_up_modes', 'id')],
            'billing_executive_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'store_incharge_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'advisor_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'technician_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'loss_reason' => ['nullable', Rule::in(array_keys(ProformaApproval::lossReasons()))],
            'missing_reason' => ['nullable', Rule::in(array_keys(ProformaApproval::missingReasons()))],
            'discount_type' => ['nullable', Rule::in(array_keys(ProformaApproval::discountTypes()))],
            'rejection_reason' => ['nullable', Rule::in(array_keys(ProformaApproval::rejectionReasons())), Rule::requiredIf(fn () => $this->status === ProformaApproval::STATUS_RETURN_FOR_CORRECTION)],
            'proforma_reference' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'store_approved_at' => ['nullable', 'date'],
            'advisor_approved_at' => ['nullable', 'date'],
            'admin_approved_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'checkpoints' => ['array'],
            'checkpoints.*.role' => ['required', Rule::in(array_keys(ProformaApproval::checkpointRoles()))],
            'checkpoints.*.checkpoint' => ['nullable', Rule::in(ProformaApproval::allCheckpointKeys())],
            'checkpoints.*.status' => ['required', Rule::in(array_keys(ProformaApproval::checkpointStatuses()))],
            'checkpoints.*.note' => ['nullable', 'string', 'max:255'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(ProformaApprovalAttachment::attachmentTypes()))],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ];
    }

    public function addCheckpoint(): void
    {
        $this->checkpoints[] = $this->blankCheckpoint();
    }

    public function removeCheckpoint(int $index): void
    {
        unset($this->checkpoints[$index]);
        $this->checkpoints = array_values($this->checkpoints);
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
    public function insuranceCompanies()
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
    public function followUpModes()
    {
        return FollowUpModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function vendors()
    {
        return VendorMaster::query()->where('is_active', true)->orderBy('name')->limit(100)->get(['id', 'name']);
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
    public function estimates()
    {
        return $this->pickerOptions(
            query: SalesEstimate::query()->latest('id'),
            searchColumns: ['estimate_no'], term: $this->estimateSearch, selected: $this->sales_estimate_id, columns: ['id', 'estimate_no'], limit: 30,
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

    /** @return array<string, string> */
    public function checkpointOptions(string $role): array
    {
        return ProformaApproval::checkpointsForRole($role);
    }

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'proforma_approval.update' : 'proforma_approval.create');

        $this->checkpoints = array_values(array_filter($this->checkpoints, fn ($c) => filled($c['checkpoint'] ?? null)));

        $data = $this->validate();
        $checkpoints = $data['checkpoints'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['checkpoints'], $data['attachments'], $data['attachmentFiles']);

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }

        $isCreate = $this->editingId === null;

        $approval = DB::transaction(function () use ($data, $checkpoints, $attachments, $isCreate) {
            if ($isCreate) {
                $data['requested_at'] = now();
                if ($data['status'] !== ProformaApproval::STATUS_UNDER_PREPARATION) {
                    $data['prepared_at'] = now();
                }
                if ($data['status'] === ProformaApproval::STATUS_CONVERTED) {
                    $data['converted_at'] = now();
                }
                $row = ProformaApproval::create($data);
                $this->editingId = $row->id;
                $this->approval_no = $row->fresh()->approval_no;
            } else {
                $row = ProformaApproval::findOrFail($this->editingId);
                if ($data['status'] !== ProformaApproval::STATUS_UNDER_PREPARATION && $row->prepared_at === null) {
                    $data['prepared_at'] = now();
                }
                if ($data['status'] === ProformaApproval::STATUS_CONVERTED && $row->converted_at === null) {
                    $data['converted_at'] = now();
                }
                $row->update($data);
            }

            $this->syncCheckpoints($row, $checkpoints);
            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Proforma approval '.$approval->fresh()->approval_no.($isCreate ? ' raised.' : ' updated.'), variant: 'success');

        return redirect()->route('proforma-approval.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncCheckpoints(ProformaApproval $approval, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = ChildRows::upsert($approval->checkpoints(), $row['id'] ?? null,
                [
                    'role' => $row['role'],
                    'checkpoint' => $row['checkpoint'],
                    'status' => $row['status'] ?: 'ok',
                    'note' => isset($row['note']) && is_string($row['note']) ? strtoupper($row['note']) : null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $approval->checkpoints()->whereKeyNot($keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(ProformaApproval $approval, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('proforma-approvals/'.$approval->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = ChildRows::upsert($approval->attachments(), $row['id'] ?? null,
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $approval->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('proforma-approval::edit');
    }
}
