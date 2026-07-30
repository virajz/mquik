<?php

namespace App\Modules\IpoCancelApproval\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InternalPartOrder\Models\InternalPartOrder;
use App\Modules\IpoCancelApproval\Models\IpoCancelApproval;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
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
#[Title('IPO Cancel Approval')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $cancel_no = null;

    public ?int $job_card_id = null;

    public ?int $internal_part_order_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $workshop_department_id = null;

    public ?int $service_type_id = null;

    public ?int $employee_id = null;

    public ?int $spare_id = null;

    public ?int $uom_id = null;

    public ?float $quantity = null;

    public ?string $cancellation_reason = null;

    /** @var array<int, string> */
    public array $impacts = [];

    public ?string $cancellation_category = null;

    public ?string $issue_status = null;

    public ?string $return_status = null;

    public ?string $return_type = null;

    public ?string $rejection_reason = null;

    public string $status = IpoCancelApproval::STATUS_UNDER_REVIEW;

    public ?string $approval_level = null;

    public ?string $decided_at = null;

    public ?string $notes = null;

    public string $spareSearch = '';

    public string $jobCardSearch = '';

    public string $ipoSearch = '';

    /** @var array<int, array{id:?int, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?IpoCancelApproval $ipoCancelApproval = null): void
    {
        if ($ipoCancelApproval && $ipoCancelApproval->exists) {
            $this->load($ipoCancelApproval);
        }
    }

    protected function load(IpoCancelApproval $a): void
    {
        $a->load('attachments');
        $this->editingId = $a->id;
        foreach ([
            'cancel_no', 'job_card_id', 'internal_part_order_id', 'customer_id', 'customer_vehicle_id',
            'workshop_department_id', 'service_type_id', 'employee_id', 'spare_id', 'uom_id',
            'cancellation_reason', 'cancellation_category', 'issue_status', 'return_status', 'return_type',
            'rejection_reason', 'status', 'approval_level', 'notes',
        ] as $k) {
            $this->{$k} = $a->{$k};
        }
        $this->quantity = $a->quantity === null ? null : (float) $a->quantity;
        $this->impacts = $a->impacts ?? [];
        $this->decided_at = $a->decided_at?->format('Y-m-d');

        $this->attachments = $a->attachments->map(fn ($x) => [
            'id' => $x->id, 'path' => $x->path, 'original_name' => $x->original_name, 'notes' => $x->notes,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'internal_part_order_id' => ['nullable', 'integer', Rule::exists('internal_part_orders', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'spare_id' => ['nullable', 'integer', Rule::exists('spares', 'id')],
            'uom_id' => ['nullable', 'integer', Rule::exists('units_of_measure', 'id')],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'cancellation_reason' => ['nullable', Rule::in(array_keys(IpoCancelApproval::cancellationReasons()))],
            'impacts' => ['array'],
            'impacts.*' => [Rule::in(array_keys(IpoCancelApproval::impactOptions()))],
            'cancellation_category' => ['nullable', Rule::in(array_keys(IpoCancelApproval::categories()))],
            'issue_status' => ['nullable', Rule::in(array_keys(IpoCancelApproval::issueStatuses()))],
            'return_status' => ['nullable', Rule::in(array_keys(IpoCancelApproval::returnStatuses()))],
            'return_type' => ['nullable', Rule::in(array_keys(IpoCancelApproval::returnTypes()))],
            'rejection_reason' => ['nullable', Rule::in(array_keys(IpoCancelApproval::rejectionReasons())), Rule::requiredIf(fn () => $this->status === IpoCancelApproval::STATUS_REJECTED)],
            'status' => ['required', Rule::in(array_keys(IpoCancelApproval::statuses()))],
            'approval_level' => ['nullable', Rule::in(array_keys(IpoCancelApproval::approvalLevels()))],
            'decided_at' => ['nullable', 'date', Rule::requiredIf(fn () => in_array($this->status, [IpoCancelApproval::STATUS_APPROVED, IpoCancelApproval::STATUS_REJECTED], true))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['array'],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ];
    }

    public function addAttachment(): void
    {
        $this->attachments[] = ['id' => null, 'path' => null, 'original_name' => null, 'notes' => null];
    }

    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index], $this->attachmentFiles[$index]);
        $this->attachments = array_values($this->attachments);
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
    public function uoms()
    {
        return UnitOfMeasureMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
    }

    #[Computed]
    public function spares()
    {
        return $this->pickerOptions(
            query: SpareMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'spare_code'],
            term: $this->spareSearch,
            selected: $this->spare_id,
            columns: ['id', 'name'],
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
    public function ipos()
    {
        return $this->pickerOptions(
            query: InternalPartOrder::query()->latest('id'),
            searchColumns: ['order_no'],
            term: $this->ipoSearch,
            selected: $this->internal_part_order_id,
            columns: ['id', 'order_no'],
            limit: 30,
        );
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'ipo_cancel_approval.update' : 'ipo_cancel_approval.create');

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);
        $data['impacts'] = array_values($data['impacts'] ?? []);

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }

        $isCreate = $this->editingId === null;

        $approval = DB::transaction(function () use ($data, $attachments, $isCreate) {
            if ($isCreate) {
                $row = IpoCancelApproval::create($data);
                $this->editingId = $row->id;
                $this->cancel_no = $row->fresh()->cancel_no;
            } else {
                $row = IpoCancelApproval::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'IPO cancel request '.$approval->fresh()->cancel_no.($isCreate ? ' raised.' : ' updated.'), variant: 'success');

        return redirect()->route('ipo-cancel-approval.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncAttachments(IpoCancelApproval $approval, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('ipo-cancel-approvals/'.$approval->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $approval->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                ['kind' => $kind, 'path' => $path, 'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1],
            )->id;
        }

        $approval->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('ipo-cancel-approval::edit');
    }
}
