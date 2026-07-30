<?php

namespace App\Modules\JobCardCancelApproval\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobCardCancelApproval\Models\JobCardCancelApproval;
use App\Modules\JobCardCancelReasonMaster\Models\JobCardCancelReasonMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Job Card Cancel Approval')]
class Edit extends Component
{
    use SearchesPickerOptions;

    public ?int $editingId = null;

    public ?string $approval_no = null;

    public ?int $job_card_id = null;

    public ?int $workshop_department_id = null;

    public ?int $service_type_id = null;

    public ?int $employee_id = null;

    public ?int $cancel_reason_id = null;

    public ?int $follow_up_mode_id = null;

    public ?string $cancellation_type = null;

    public ?string $approval_level = null;

    public string $status = JobCardCancelApproval::STATUS_PENDING;

    /** @var array<int, string> */
    public array $impacts = [];

    public ?string $approval_rejection_reason = null;

    public ?string $refund_status = null;

    public ?string $decided_at = null;

    public ?string $notes = null;

    public string $jobCardSearch = '';

    public function mount(?JobCardCancelApproval $jobCardCancelApproval = null): void
    {
        if ($jobCardCancelApproval && $jobCardCancelApproval->exists) {
            $this->load($jobCardCancelApproval);
        }
    }

    protected function load(JobCardCancelApproval $a): void
    {
        $this->editingId = $a->id;
        foreach ([
            'approval_no', 'job_card_id', 'workshop_department_id', 'service_type_id', 'employee_id',
            'cancel_reason_id', 'follow_up_mode_id', 'cancellation_type', 'approval_level', 'status',
            'approval_rejection_reason', 'refund_status', 'notes',
        ] as $k) {
            $this->{$k} = $a->{$k};
        }
        $this->impacts = $a->impacts ?? [];
        $this->decided_at = $a->decided_at?->format('Y-m-d');
    }

    protected function rules(): array
    {
        return [
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'cancel_reason_id' => ['nullable', 'integer', Rule::exists('job_card_cancel_reasons', 'id')],
            'follow_up_mode_id' => ['nullable', 'integer', Rule::exists('follow_up_modes', 'id')],
            'cancellation_type' => ['nullable', Rule::in(array_keys(JobCardCancelApproval::cancellationTypes()))],
            'approval_level' => ['nullable', Rule::in(array_keys(JobCardCancelApproval::approvalLevels()))],
            'status' => ['required', Rule::in(array_keys(JobCardCancelApproval::statuses()))],
            'impacts' => ['array'],
            'impacts.*' => [Rule::in(array_keys(JobCardCancelApproval::impactOptions()))],
            'approval_rejection_reason' => ['nullable', Rule::in(array_keys(JobCardCancelApproval::rejectionReasons())), Rule::requiredIf(fn () => $this->status === JobCardCancelApproval::STATUS_REJECTED)],
            'refund_status' => ['nullable', Rule::in(array_keys(JobCardCancelApproval::refundStatuses()))],
            'decided_at' => ['nullable', 'date', Rule::requiredIf(fn () => in_array($this->status, [JobCardCancelApproval::STATUS_APPROVED, JobCardCancelApproval::STATUS_REJECTED], true))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
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
    public function cancelReasons()
    {
        return JobCardCancelReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function followUpModes()
    {
        return FollowUpModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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

    public function save()
    {
        $this->authorize($this->editingId ? 'job_card_cancel_approval.update' : 'job_card_cancel_approval.create');

        $data = $this->validate();
        $data['impacts'] = array_values($data['impacts'] ?? []);

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }

        $isCreate = $this->editingId === null;

        if ($isCreate) {
            $a = JobCardCancelApproval::create($data);
            $this->editingId = $a->id;
        } else {
            $a = JobCardCancelApproval::findOrFail($this->editingId);
            $a->update($data);
        }

        Flux::toast(text: 'Cancel approval '.$a->fresh()->approval_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('job-card-cancel-approval.index');
    }

    public function render()
    {
        return view('job-card-cancel-approval::edit');
    }
}
