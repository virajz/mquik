<?php

namespace App\Modules\GatePassApproval\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\GatePassApproval\Models\GatePassApproval;
use App\Modules\GatePassApproval\Models\GatePassApprovalAttachment;
use App\Modules\JobCard\Models\JobCard;
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
#[Title('Gate Pass Approval')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $approval_no = null;

    public string $priority = 'normal';

    public ?string $credit_type = null;

    public ?string $credit_reason = null;

    public ?string $customer_commitment = null;

    public ?string $security_deposit = null;

    public ?string $risk_type = null;

    public ?string $risk_category = null;

    public ?string $approval_authority = null;

    public string $status = GatePassApproval::STATUS_REQUESTED;

    public ?string $cancellation_reason = null;

    public ?int $workshop_department_id = null;

    public ?int $requested_by_id = null;

    public ?int $job_card_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $follow_up_mode_id = null;

    public ?string $invoice_reference = null;

    public ?string $po_reference = null;

    public ?string $receipt_reference = null;

    public ?string $outstanding_reference = null;

    public ?float $invoice_amount = null;

    public ?float $receipt_amount = null;

    public ?float $outstanding_amount = null;

    public ?float $credit_exposure = null;

    public ?string $notes = null;

    public string $jobCardSearch = '';

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?GatePassApproval $gatePassApproval = null): void
    {
        if ($gatePassApproval && $gatePassApproval->exists) {
            $this->load($gatePassApproval);
        }
    }

    protected function load(GatePassApproval $g): void
    {
        $g->load('attachments');
        $this->editingId = $g->id;
        foreach ([
            'approval_no', 'priority', 'credit_type', 'credit_reason', 'customer_commitment', 'security_deposit',
            'risk_type', 'risk_category', 'approval_authority', 'status', 'cancellation_reason',
            'workshop_department_id', 'requested_by_id', 'job_card_id', 'customer_id', 'customer_vehicle_id',
            'follow_up_mode_id', 'invoice_reference', 'po_reference', 'receipt_reference', 'outstanding_reference', 'notes',
        ] as $k) {
            $this->{$k} = $g->{$k};
        }
        foreach (['invoice_amount', 'receipt_amount', 'outstanding_amount', 'credit_exposure'] as $k) {
            $this->{$k} = $g->{$k} === null ? null : (float) $g->{$k};
        }

        $this->attachments = $g->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    /** Recompute outstanding / credit exposure / approval authority from the amounts. */
    protected function recalculate(): void
    {
        $invoice = (float) ($this->invoice_amount ?? 0);
        $receipt = (float) ($this->receipt_amount ?? 0);
        $outstanding = round($invoice - $receipt, 2);

        $this->outstanding_amount = $outstanding;
        $this->credit_exposure = $outstanding;
        $this->approval_authority = GatePassApproval::authorityForAmount($outstanding);
    }

    public function updatedInvoiceAmount(): void
    {
        $this->recalculate();
    }

    public function updatedReceiptAmount(): void
    {
        $this->recalculate();
    }

    protected function rules(): array
    {
        return [
            'priority' => ['required', Rule::in(array_keys(GatePassApproval::priorities()))],
            'credit_type' => ['nullable', Rule::in(array_keys(GatePassApproval::creditTypes()))],
            'credit_reason' => ['nullable', Rule::in(array_keys(GatePassApproval::creditReasons()))],
            'customer_commitment' => ['nullable', Rule::in(array_keys(GatePassApproval::customerCommitments()))],
            'security_deposit' => ['nullable', Rule::in(array_keys(GatePassApproval::securityDeposits()))],
            'risk_type' => ['nullable', Rule::in(array_keys(GatePassApproval::riskTypes()))],
            'risk_category' => ['nullable', Rule::in(array_keys(GatePassApproval::riskCategories()))],
            'approval_authority' => ['nullable', Rule::in(array_keys(GatePassApproval::approvalAuthorities()))],
            'status' => ['required', Rule::in(array_keys(GatePassApproval::statuses()))],
            'cancellation_reason' => ['nullable', Rule::in(array_keys(GatePassApproval::cancellationReasons())), Rule::requiredIf(fn () => $this->status === GatePassApproval::STATUS_CANCELLED)],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'requested_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'follow_up_mode_id' => ['nullable', 'integer', Rule::exists('follow_up_modes', 'id')],
            'invoice_reference' => ['nullable', 'string', 'max:255'],
            'po_reference' => ['nullable', 'string', 'max:255'],
            'receipt_reference' => ['nullable', 'string', 'max:255'],
            'outstanding_reference' => ['nullable', 'string', 'max:255'],
            'invoice_amount' => ['nullable', 'numeric', 'min:0'],
            'receipt_amount' => ['nullable', 'numeric', 'min:0'],
            'outstanding_amount' => ['nullable', 'numeric'],
            'credit_exposure' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(GatePassApprovalAttachment::attachmentTypes()))],
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
    public function followUpModes()
    {
        return FollowUpModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
        $this->authorize($this->editingId ? 'gate_pass_approval.update' : 'gate_pass_approval.create');

        $this->recalculate();

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);

        foreach (['invoice_reference', 'po_reference', 'receipt_reference', 'outstanding_reference', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $approval = DB::transaction(function () use ($data, $attachments, $isCreate) {
            if ($isCreate) {
                $data['requested_at'] = now();
                $data = $this->applyStatusTimestamps($data, null);
                $row = GatePassApproval::create($data);
                $this->editingId = $row->id;
                $this->approval_no = $row->fresh()->approval_no;
            } else {
                $row = GatePassApproval::findOrFail($this->editingId);
                $data = $this->applyStatusTimestamps($data, $row);
                $row->update($data);
            }

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Gate pass approval '.$approval->fresh()->approval_no.($isCreate ? ' raised.' : ' updated.'), variant: 'success');

        return redirect()->route('gate-pass-approval.index');
    }

    /**
     * Stamp the approved / rejected / cancelled timestamp the first time the status hits it.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyStatusTimestamps(array $data, ?GatePassApproval $existing): array
    {
        $approved = GatePassApproval::approvedStatuses();

        if (in_array($data['status'], $approved, true) && ($existing?->approved_at === null)) {
            $data['approved_at'] = now();
        }
        if ($data['status'] === GatePassApproval::STATUS_REJECTED && ($existing?->rejected_at === null)) {
            $data['rejected_at'] = now();
        }
        if ($data['status'] === GatePassApproval::STATUS_CANCELLED && ($existing?->cancelled_at === null)) {
            $data['cancelled_at'] = now();
        }

        return $data;
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(GatePassApproval $approval, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('gate-pass-approvals/'.$approval->id, 'public');
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
        return view('gate-pass-approval::edit');
    }
}
