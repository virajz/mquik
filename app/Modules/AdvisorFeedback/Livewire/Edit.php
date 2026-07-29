<?php

namespace App\Modules\AdvisorFeedback\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\AdvisorFeedback\Models\AdvisorFeedback;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GatePassApproval\Models\GatePassApproval;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Advisor Feedback')]
class Edit extends Component
{
    use SearchesPickerOptions;

    public ?int $editingId = null;

    public ?string $feedback_no = null;

    public string $status = AdvisorFeedback::STATUS_PENDING;

    public ?int $workshop_department_id = null;

    public ?int $service_type_id = null;

    public ?int $advisor_id = null;

    public ?int $technician_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $gate_pass_approval_id = null;

    public ?string $invoice_reference = null;

    public ?int $cooperative_rating = null;

    public ?int $timely_approvals_rating = null;

    public ?int $payment_committed_rating = null;

    public ?int $professional_rating = null;

    public ?int $prefer_again_rating = null;

    public ?string $notes = null;

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    public string $gatePassSearch = '';

    public function mount(?AdvisorFeedback $advisorFeedback = null): void
    {
        if ($advisorFeedback && $advisorFeedback->exists) {
            $this->load($advisorFeedback);
        }
    }

    protected function load(AdvisorFeedback $f): void
    {
        $this->editingId = $f->id;
        foreach ([
            'feedback_no', 'status', 'workshop_department_id', 'service_type_id', 'advisor_id', 'technician_id',
            'customer_id', 'customer_vehicle_id', 'gate_pass_approval_id', 'invoice_reference',
            'cooperative_rating', 'timely_approvals_rating', 'payment_committed_rating', 'professional_rating',
            'prefer_again_rating', 'notes',
        ] as $k) {
            $this->{$k} = $f->{$k};
        }
    }

    protected function rules(): array
    {
        $rating = ['nullable', 'integer', 'between:1,5'];

        return [
            'status' => ['required', Rule::in(array_keys(AdvisorFeedback::statuses()))],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')],
            'advisor_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'technician_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'gate_pass_approval_id' => ['nullable', 'integer', Rule::exists('gate_pass_approvals', 'id')],
            'invoice_reference' => ['nullable', 'string', 'max:255'],
            'cooperative_rating' => $rating,
            'timely_approvals_rating' => $rating,
            'payment_committed_rating' => $rating,
            'professional_rating' => $rating,
            'prefer_again_rating' => $rating,
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
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

    #[Computed]
    public function gatePasses()
    {
        return $this->pickerOptions(
            query: GatePassApproval::query()->latest('id'),
            searchColumns: ['approval_no'], term: $this->gatePassSearch, selected: $this->gate_pass_approval_id, columns: ['id', 'approval_no'], limit: 30,
        );
    }

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'advisor_feedback.update' : 'advisor_feedback.create');

        $data = $this->validate();

        if (isset($data['invoice_reference']) && is_string($data['invoice_reference'])) {
            $data['invoice_reference'] = strtoupper($data['invoice_reference']);
        }
        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }

        $isCreate = $this->editingId === null;

        if ($isCreate) {
            if ($data['status'] === AdvisorFeedback::STATUS_SUBMITTED) {
                $data['submitted_at'] = now();
            }
            $feedback = AdvisorFeedback::create($data);
            $this->editingId = $feedback->id;
            $this->feedback_no = $feedback->fresh()->feedback_no;
        } else {
            $feedback = AdvisorFeedback::findOrFail($this->editingId);
            if ($data['status'] === AdvisorFeedback::STATUS_SUBMITTED && $feedback->submitted_at === null) {
                $data['submitted_at'] = now();
            }
            $feedback->update($data);
        }

        Flux::toast(text: 'Advisor feedback '.$feedback->fresh()->feedback_no.($isCreate ? ' recorded.' : ' updated.'), variant: 'success');

        return redirect()->route('advisor-feedback.index');
    }

    public function render()
    {
        return view('advisor-feedback::edit');
    }
}
