<?php

namespace App\Modules\PolicyRenewalFollowUp\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\InsurancePolicyTypeMaster\Models\InsurancePolicyTypeMaster;
use App\Modules\PolicyRenewalFollowUp\Models\PolicyRenewalFollowUp;
use App\Modules\PolicyRenewalFollowUp\Models\PolicyRenewalFollowUpAttachment;
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
#[Title('Policy Renewal Follow-Ups')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $follow_up_no = null;

    public ?int $insurance_company_id = null;

    public ?int $insurance_policy_type_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $assigned_by_id = null;

    public ?int $assigned_to_id = null;

    public ?string $renewal_reference = null;

    public ?string $policy_number = null;

    public ?string $policy_start_date = null;

    public ?string $policy_end_date = null;

    public string $priority = 'normal';

    public string $status = PolicyRenewalFollowUp::STATUS_PENDING;

    public ?string $reminder_frequency = null;

    public ?string $follow_up_attempt = null;

    public ?string $follow_up_mode = null;

    public ?string $customer_response = null;

    public ?string $lost_reason = null;

    public ?string $escalation = null;

    public ?string $escalation_reason = null;

    public ?string $customer_retention = 'active';

    public ?float $renewal_premium = null;

    public ?string $notes = null;

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?PolicyRenewalFollowUp $policyRenewalFollowUp = null): void
    {
        if ($policyRenewalFollowUp && $policyRenewalFollowUp->exists) {
            $this->load($policyRenewalFollowUp);
        }
    }

    protected function load(PolicyRenewalFollowUp $f): void
    {
        $f->load('attachments');
        $this->editingId = $f->id;
        foreach ([
            'follow_up_no', 'insurance_company_id', 'insurance_policy_type_id', 'customer_id', 'customer_vehicle_id',
            'assigned_by_id', 'assigned_to_id', 'renewal_reference', 'policy_number', 'priority', 'status',
            'reminder_frequency', 'follow_up_attempt', 'follow_up_mode', 'customer_response', 'lost_reason',
            'escalation', 'escalation_reason', 'customer_retention', 'notes',
        ] as $k) {
            $this->{$k} = $f->{$k};
        }
        $this->policy_start_date = $f->policy_start_date?->format('Y-m-d');
        $this->policy_end_date = $f->policy_end_date?->format('Y-m-d');
        $this->renewal_premium = $f->renewal_premium === null ? null : (float) $f->renewal_premium;

        $this->attachments = $f->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'insurance_company_id' => ['nullable', 'integer', Rule::exists('insurance_companies', 'id')],
            'insurance_policy_type_id' => ['nullable', 'integer', Rule::exists('insurance_policy_types', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'assigned_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'assigned_to_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'renewal_reference' => ['nullable', 'string', 'max:255'],
            'policy_number' => ['nullable', 'string', 'max:80'],
            'policy_start_date' => ['nullable', 'date'],
            'policy_end_date' => ['nullable', 'date'],
            'priority' => ['required', Rule::in(array_keys(PolicyRenewalFollowUp::priorities()))],
            'status' => ['required', Rule::in(array_keys(PolicyRenewalFollowUp::statuses()))],
            'reminder_frequency' => ['nullable', Rule::in(array_keys(PolicyRenewalFollowUp::reminderFrequencies()))],
            'follow_up_attempt' => ['nullable', Rule::in(array_keys(PolicyRenewalFollowUp::followUpAttempts()))],
            'follow_up_mode' => ['nullable', Rule::in(array_keys(PolicyRenewalFollowUp::followUpModes()))],
            'customer_response' => ['nullable', Rule::in(array_keys(PolicyRenewalFollowUp::customerResponses()))],
            'lost_reason' => ['nullable', Rule::in(array_keys(PolicyRenewalFollowUp::lostReasons())), Rule::requiredIf(fn () => $this->status === PolicyRenewalFollowUp::STATUS_LOST)],
            'escalation' => ['nullable', Rule::in(array_keys(PolicyRenewalFollowUp::escalations()))],
            'escalation_reason' => ['nullable', Rule::in(array_keys(PolicyRenewalFollowUp::escalationReasons())), Rule::requiredIf(fn () => filled($this->escalation))],
            'customer_retention' => ['nullable', Rule::in(array_keys(PolicyRenewalFollowUp::retentions()))],
            'renewal_premium' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(PolicyRenewalFollowUpAttachment::attachmentTypes()))],
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
    public function insuranceCompanies()
    {
        return InsuranceCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function policyTypes()
    {
        return InsurancePolicyTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
        $this->authorize($this->editingId ? 'policy_renewal_follow_up.update' : 'policy_renewal_follow_up.create');

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);

        foreach (['renewal_reference', 'policy_number', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $followUp = DB::transaction(function () use ($data, $attachments, $isCreate) {
            $existing = $isCreate ? null : PolicyRenewalFollowUp::findOrFail($this->editingId);
            $data = $this->applyTimestamps($data, $existing);

            if ($isCreate) {
                $row = PolicyRenewalFollowUp::create($data);
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

        Flux::toast(text: 'Renewal follow-up '.$followUp->fresh()->follow_up_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('policy-renewal-follow-up.index');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyTimestamps(array $data, ?PolicyRenewalFollowUp $existing): array
    {
        if (filled($data['customer_response'] ?? null) && ($existing?->response_at === null)) {
            $data['response_at'] = now();
        }
        if (($data['status'] ?? null) === PolicyRenewalFollowUp::STATUS_QUOTATION_SENT && ($existing?->quote_shared_at === null)) {
            $data['quote_shared_at'] = now();
        }
        if (($data['status'] ?? null) === PolicyRenewalFollowUp::STATUS_RENEWED && ($existing?->policy_issued_at === null)) {
            $data['policy_issued_at'] = now();
            if (($existing?->payment_received_at ?? null) === null) {
                $data['payment_received_at'] = now();
            }
        }

        return $data;
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(PolicyRenewalFollowUp $followUp, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('policy-renewal-follow-ups/'.$followUp->id, 'public');
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
        return view('policy-renewal-follow-up::edit');
    }
}
