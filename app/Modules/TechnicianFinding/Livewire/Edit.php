<?php

namespace App\Modules\TechnicianFinding\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TechnicianFinding\Models\TechnicianFinding;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Technician Finding')]
class Edit extends Component
{
    public ?int $editingId = null;

    public ?string $finding_no = null;

    public ?int $job_card_id = null;

    public ?int $vehicle_inspection_order_id = null;

    public string $finding_type = TechnicianFinding::TYPE_SPARE;

    public ?int $spare_id = null;

    public ?int $labour_id = null;

    public ?int $reported_by_id = null;

    public string $description = '';

    public ?string $quantity = null;

    public ?string $estimated_amount = null;

    public string $recommendation = 'new_issue';

    public string $status = TechnicianFinding::STATUS_RECOMMENDED;

    public ?string $notes = null;

    #[Url(as: 'from-job-card')]
    public ?int $fromJobCard = null;

    #[Url(as: 'from-order')]
    public ?int $fromOrder = null;

    public function mount(?TechnicianFinding $technicianFinding = null): void
    {
        if ($technicianFinding && $technicianFinding->exists) {
            $this->load($technicianFinding);

            return;
        }

        if ($this->fromJobCard) {
            $this->job_card_id = $this->fromJobCard;
            $this->prefillFromJobCard();
        }
        if ($this->fromOrder) {
            $this->vehicle_inspection_order_id = $this->fromOrder;
            $order = VehicleInspectionOrder::find($this->fromOrder);
            if ($order && ! $this->job_card_id) {
                $this->job_card_id = $order->job_card_id;
                $this->prefillFromJobCard();
            }
        }
    }

    /** Picking a job card in the form should fill the same things the URL handoff does. */
    public function updatedJobCardId(): void
    {
        $this->prefillFromJobCard();
    }

    /**
     * A finding is raised by whoever is on the vehicle, against the work order
     * they are running — both derivable from the job card.
     */
    protected function prefillFromJobCard(): void
    {
        if (! $this->job_card_id) {
            return;
        }

        $jobCard = JobCard::find($this->job_card_id);
        if (! $jobCard) {
            return;
        }

        $this->reported_by_id ??= $jobCard->assigned_technician_id;

        // Attach to the card's current work order when there is exactly one
        // obvious candidate; ambiguity is left for the user to resolve.
        if (! $this->vehicle_inspection_order_id) {
            $orders = VehicleInspectionOrder::query()
                ->where('job_card_id', $jobCard->id)
                ->orderByDesc('id')
                ->limit(2)
                ->get(['id']);

            if ($orders->count() === 1) {
                $this->vehicle_inspection_order_id = $orders->first()->id;
            }
        }
    }

    protected function load(TechnicianFinding $finding): void
    {
        $this->editingId = $finding->id;
        $this->finding_no = $finding->finding_no;
        $this->job_card_id = $finding->job_card_id;
        $this->vehicle_inspection_order_id = $finding->vehicle_inspection_order_id;
        $this->finding_type = $finding->finding_type;
        $this->spare_id = $finding->spare_id;
        $this->labour_id = $finding->labour_id;
        $this->reported_by_id = $finding->reported_by_id;
        $this->description = $finding->description;
        $this->quantity = $finding->quantity !== null ? (string) $finding->quantity : null;
        $this->estimated_amount = $finding->estimated_amount !== null ? (string) $finding->estimated_amount : null;
        $this->recommendation = $finding->recommendation;
        $this->status = $finding->status;
        $this->notes = $finding->notes;
    }

    protected function rules(): array
    {
        return [
            'job_card_id' => ['required', 'integer', 'exists:job_cards,id'],
            'vehicle_inspection_order_id' => ['nullable', 'integer', 'exists:vehicle_inspection_orders,id'],
            'finding_type' => ['required', Rule::in(array_keys(TechnicianFinding::types()))],
            'spare_id' => ['nullable', 'integer', 'exists:spares,id'],
            'labour_id' => ['nullable', 'integer', 'exists:labours,id'],
            'reported_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'estimated_amount' => ['nullable', 'numeric', 'min:0'],
            'recommendation' => ['required', Rule::in(array_keys(TechnicianFinding::recommendations()))],
            'status' => ['required', Rule::in(array_keys(TechnicianFinding::statuses()))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    #[Computed]
    public function jobCards()
    {
        return JobCard::query()
            ->with(['customer:id,first_name,last_name', 'customerVehicle:id,registration_no'])
            ->orderByDesc('opened_at')
            ->limit(100)
            ->get(['id', 'job_card_no', 'customer_id', 'customer_vehicle_id', 'opened_at']);
    }

    #[Computed]
    public function orders()
    {
        return VehicleInspectionOrder::query()
            ->when($this->job_card_id, fn ($q) => $q->where('job_card_id', $this->job_card_id))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'order_no', 'job_card_id']);
    }

    #[Computed]
    public function spares()
    {
        return SpareMaster::query()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name']);
    }

    #[Computed]
    public function labours()
    {
        return LabourMaster::query()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'technician_finding.update' : 'technician_finding.create');

        $data = $this->validate();

        // Keep the spare/labour link consistent with the chosen type.
        if ($data['finding_type'] === TechnicianFinding::TYPE_SPARE) {
            $data['labour_id'] = null;
        } else {
            $data['spare_id'] = null;
        }

        foreach (['description', 'notes'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = strtoupper($data[$field]);
            }
        }

        $isCreate = $this->editingId === null;

        if ($isCreate) {
            $finding = TechnicianFinding::create($data);
            $this->editingId = $finding->id;
            $this->finding_no = $finding->fresh()->finding_no;
        } else {
            $finding = TechnicianFinding::findOrFail($this->editingId);
            $finding->update($data);
        }

        Flux::toast(
            text: 'Finding '.$finding->fresh()->finding_no.($isCreate ? ' recorded.' : ' updated.'),
            variant: 'success',
        );

        return redirect()->route('technician-finding.index');
    }

    public function render()
    {
        return view('technician-finding::edit');
    }
}
