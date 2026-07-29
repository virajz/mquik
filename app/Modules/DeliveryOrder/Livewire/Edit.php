<?php

namespace App\Modules\DeliveryOrder\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DeliveryOrder\Models\DeliveryOrder;
use App\Modules\DeliveryOrder\Models\DeliveryOrderAttachment;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\ProformaApproval\Models\ProformaApproval;
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
#[Title('Delivery Order (DO)')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $do_no = null;

    public ?int $job_card_id = null;

    public ?int $proforma_approval_id = null;

    public ?int $workshop_department_id = null;

    public ?int $employee_id = null;

    public ?int $insurance_company_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $follow_up_mode_id = null;

    public ?string $surveyor_name = null;

    public ?string $claim_number = null;

    public ?string $claim_date = null;

    public ?string $policy_number = null;

    public string $status = DeliveryOrder::STATUS_REQUESTED;

    public ?string $mismatch_reason = null;

    public ?float $proforma_amount = null;

    public ?float $do_amount = null;

    public ?string $do_description = null;

    public ?string $reminder_frequency = null;

    public ?int $reminder_custom_days = null;

    public ?string $do_received_at = null;

    public ?string $do_entry_at = null;

    public ?string $notes = null;

    public string $jobCardSearch = '';

    public string $proformaSearch = '';

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?DeliveryOrder $deliveryOrder = null): void
    {
        if ($deliveryOrder && $deliveryOrder->exists) {
            $this->load($deliveryOrder);
        }
    }

    protected function load(DeliveryOrder $d): void
    {
        $d->load('attachments');
        $this->editingId = $d->id;
        foreach ([
            'do_no', 'job_card_id', 'proforma_approval_id', 'workshop_department_id', 'employee_id',
            'insurance_company_id', 'customer_id', 'customer_vehicle_id', 'follow_up_mode_id', 'surveyor_name',
            'claim_number', 'policy_number', 'status', 'mismatch_reason', 'do_description',
            'reminder_frequency', 'reminder_custom_days', 'notes',
        ] as $k) {
            $this->{$k} = $d->{$k};
        }
        $this->proforma_amount = $d->proforma_amount === null ? null : (float) $d->proforma_amount;
        $this->do_amount = $d->do_amount === null ? null : (float) $d->do_amount;
        $this->claim_date = $d->claim_date?->format('Y-m-d');
        $this->do_received_at = $d->do_received_at?->format('Y-m-d\TH:i');
        $this->do_entry_at = $d->do_entry_at?->format('Y-m-d\TH:i');

        $this->attachments = $d->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'proforma_approval_id' => ['nullable', 'integer', Rule::exists('proforma_approvals', 'id')],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'insurance_company_id' => ['nullable', 'integer', Rule::exists('insurance_companies', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'follow_up_mode_id' => ['nullable', 'integer', Rule::exists('follow_up_modes', 'id')],
            'surveyor_name' => ['nullable', 'string', 'max:255'],
            'claim_number' => ['nullable', 'string', 'max:80'],
            'claim_date' => ['nullable', 'date'],
            'policy_number' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in(array_keys(DeliveryOrder::statuses()))],
            'mismatch_reason' => ['nullable', Rule::in(array_keys(DeliveryOrder::mismatchReasons())), Rule::requiredIf(fn () => $this->hasMismatch())],
            'proforma_amount' => ['nullable', 'numeric', 'min:0'],
            'do_amount' => ['nullable', 'numeric', 'min:0'],
            'do_description' => ['nullable', 'string', 'max:2000'],
            'reminder_frequency' => ['nullable', Rule::in(array_keys(DeliveryOrder::reminderFrequencies()))],
            'reminder_custom_days' => ['nullable', 'integer', 'min:1', 'max:90', Rule::requiredIf(fn () => $this->reminder_frequency === 'custom')],
            'do_received_at' => ['nullable', 'date'],
            'do_entry_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(DeliveryOrderAttachment::attachmentTypes()))],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ];
    }

    /** Live mismatch flag for the reveal + validation. */
    public function hasMismatch(): bool
    {
        return $this->do_amount !== null
            && $this->proforma_amount !== null
            && (float) $this->do_amount !== (float) $this->proforma_amount;
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

    /** Pull the proforma amount from the selected proforma approval. */
    public function updatedProformaApprovalId($value): void
    {
        if (! $value) {
            return;
        }

        $proforma = ProformaApproval::find($value);
        if ($proforma && $proforma->amount !== null && $this->proforma_amount === null) {
            $this->proforma_amount = (float) $proforma->amount;
        }
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
    public function proformas()
    {
        return $this->pickerOptions(
            query: ProformaApproval::query()->latest('id'),
            searchColumns: ['approval_no'], term: $this->proformaSearch, selected: $this->proforma_approval_id, columns: ['id', 'approval_no'], limit: 30,
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
        $this->authorize($this->editingId ? 'delivery_order.update' : 'delivery_order.create');

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);

        foreach (['claim_number', 'policy_number', 'surveyor_name', 'notes', 'do_description'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }
        if ($data['reminder_frequency'] !== 'custom') {
            $data['reminder_custom_days'] = null;
        }

        $isCreate = $this->editingId === null;

        $order = DB::transaction(function () use ($data, $attachments, $isCreate) {
            if ($isCreate) {
                $data['requested_at'] = now();
                if (in_array($data['status'], [DeliveryOrder::STATUS_RECEIVED, DeliveryOrder::STATUS_MISMATCH_APPROVED, DeliveryOrder::STATUS_REQUESTED_TO_SETTLE], true) && empty($data['do_received_at'])) {
                    $data['do_received_at'] = now();
                }
                if ($data['status'] === DeliveryOrder::STATUS_MISMATCH_APPROVED) {
                    $data['approved_at'] = now();
                }
                $row = DeliveryOrder::create($data);
                $this->editingId = $row->id;
                $this->do_no = $row->fresh()->do_no;
            } else {
                $row = DeliveryOrder::findOrFail($this->editingId);
                if (in_array($data['status'], [DeliveryOrder::STATUS_RECEIVED, DeliveryOrder::STATUS_MISMATCH_APPROVED, DeliveryOrder::STATUS_REQUESTED_TO_SETTLE], true) && $row->do_received_at === null && empty($data['do_received_at'])) {
                    $data['do_received_at'] = now();
                }
                if ($data['status'] === DeliveryOrder::STATUS_MISMATCH_APPROVED && $row->approved_at === null) {
                    $data['approved_at'] = now();
                }
                $row->update($data);
            }

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Delivery Order '.$order->fresh()->do_no.($isCreate ? ' recorded.' : ' updated.'), variant: 'success');

        return redirect()->route('delivery-order.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(DeliveryOrder $order, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('delivery-orders/'.$order->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $order->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $order->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('delivery-order::edit');
    }
}
