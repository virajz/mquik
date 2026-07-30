<?php

namespace App\Modules\ClaimIntimation\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\ClaimIntimation\Models\ClaimIntimation;
use App\Modules\ClaimTypeMaster\Models\ClaimTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\InsurancePolicyTypeMaster\Models\InsurancePolicyTypeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Claim Intimation')]
class Edit extends Component
{
    use SearchesPickerOptions;

    public ?int $editingId = null;

    public ?string $intimation_no = null;

    public ?int $job_card_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $workshop_department_id = null;

    public ?int $employee_id = null;

    public ?int $insurance_company_id = null;

    public ?int $insurance_policy_type_id = null;

    public ?string $policy_no = null;

    public ?int $claim_type_id = null;

    public ?string $claim_no = null;

    public ?string $damage_nature = null;

    public ?string $intimation_mode = null;

    public string $status = ClaimIntimation::STATUS_PENDING;

    public ?string $pending_reason = null;

    public ?string $survey_tat = null;

    public ?string $intimated_at = null;

    public ?string $notes = null;

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    public string $jobCardSearch = '';

    public function mount(?ClaimIntimation $claimIntimation = null): void
    {
        if ($claimIntimation && $claimIntimation->exists) {
            $this->load($claimIntimation);
        }
    }

    protected function load(ClaimIntimation $c): void
    {
        $this->editingId = $c->id;
        foreach ([
            'intimation_no', 'job_card_id', 'customer_id', 'customer_vehicle_id', 'workshop_department_id',
            'employee_id', 'insurance_company_id', 'insurance_policy_type_id', 'policy_no', 'claim_type_id',
            'claim_no', 'damage_nature', 'intimation_mode', 'status', 'pending_reason', 'survey_tat', 'notes',
        ] as $k) {
            $this->{$k} = $c->{$k};
        }
        $this->intimated_at = $c->intimated_at?->format('Y-m-d');
    }

    protected function rules(): array
    {
        return [
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'insurance_company_id' => ['nullable', 'integer', Rule::exists('insurance_companies', 'id')],
            'insurance_policy_type_id' => ['nullable', 'integer', Rule::exists('insurance_policy_types', 'id')],
            'policy_no' => ['nullable', 'string', 'max:100'],
            'claim_type_id' => ['nullable', 'integer', Rule::exists('claim_types', 'id')],
            'claim_no' => ['nullable', 'string', 'max:100'],
            'damage_nature' => ['nullable', Rule::in(array_keys(ClaimIntimation::damageNatures()))],
            'intimation_mode' => ['nullable', Rule::in(array_keys(ClaimIntimation::intimationModes()))],
            'status' => ['required', Rule::in(array_keys(ClaimIntimation::statuses()))],
            'pending_reason' => ['nullable', Rule::in(array_keys(ClaimIntimation::pendingReasons()))],
            'survey_tat' => ['nullable', Rule::in(array_keys(ClaimIntimation::surveyTats()))],
            'intimated_at' => ['nullable', 'date', Rule::requiredIf(fn () => $this->status === ClaimIntimation::STATUS_INTIMATED)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
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
    public function companies()
    {
        return InsuranceCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function policyTypes()
    {
        return InsurancePolicyTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function claimTypes()
    {
        return ClaimTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function customers()
    {
        return $this->pickerOptions(
            query: CustomerMaster::query()->where('is_active', true)->orderBy('first_name'),
            searchColumns: ['first_name', 'last_name', 'phone'],
            term: $this->customerSearch,
            selected: $this->customer_id,
            columns: ['id', 'first_name', 'last_name'],
            limit: 30,
        );
    }

    #[Computed]
    public function vehicles()
    {
        return $this->pickerOptions(
            query: CustomerVehicleMaster::query()->orderBy('registration_no'),
            searchColumns: ['registration_no'],
            term: $this->vehicleSearch,
            selected: $this->customer_vehicle_id,
            columns: ['id', 'registration_no'],
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

    public function save()
    {
        $this->authorize($this->editingId ? 'claim_intimation.update' : 'claim_intimation.create');

        $data = $this->validate();

        foreach (['policy_no', 'claim_no', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        if ($isCreate) {
            $c = ClaimIntimation::create($data);
            $this->editingId = $c->id;
        } else {
            $c = ClaimIntimation::findOrFail($this->editingId);
            $c->update($data);
        }

        Flux::toast(text: 'Claim intimation '.$c->fresh()->intimation_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('claim-intimation.index');
    }

    public function render()
    {
        return view('claim-intimation::edit');
    }
}
