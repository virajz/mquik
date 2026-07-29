<?php

namespace App\Modules\QueueManagement\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\QueueManagement\Models\ServiceQueue;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Queue Management')]
class Edit extends Component
{
    use SearchesPickerOptions;

    public ?int $editingId = null;

    public ?string $queue_no = null;

    public ?string $queue_type = 'car_wash';

    public ?int $job_card_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $labour_id = null;

    public ?int $technician_id = null;

    public ?string $job_description = null;

    public string $ordering_mode = 'fifo';

    public string $screen_view = 'upcoming';

    public string $status = ServiceQueue::STATUS_WAITING;

    public bool $is_high_priority = false;

    public ?string $high_priority_reason = null;

    public ?int $hp_requested_by_id = null;

    public ?int $hp_requested_to_id = null;

    public ?string $rework_reason = null;

    public ?string $delay_reason = null;

    public ?string $pause_reason = null;

    public ?string $promised_delivery_at = null;

    public ?string $expected_completion_at = null;

    public ?string $kept_at = null;

    public ?string $work_started_at = null;

    public ?string $work_ended_at = null;

    public ?string $notes = null;

    public string $vehicleSearch = '';

    public string $jobCardSearch = '';

    public function mount(?ServiceQueue $serviceQueue = null): void
    {
        if ($serviceQueue && $serviceQueue->exists) {
            $this->load($serviceQueue);

            return;
        }

        $this->kept_at = now()->format('Y-m-d\TH:i');
    }

    protected function load(ServiceQueue $q): void
    {
        $this->editingId = $q->id;
        foreach ([
            'queue_no', 'queue_type', 'job_card_id', 'customer_vehicle_id', 'labour_id', 'technician_id',
            'job_description', 'ordering_mode', 'screen_view', 'status', 'is_high_priority',
            'high_priority_reason', 'hp_requested_by_id', 'hp_requested_to_id', 'rework_reason',
            'delay_reason', 'pause_reason', 'notes',
        ] as $k) {
            $this->{$k} = $q->{$k};
        }
        foreach (['promised_delivery_at', 'expected_completion_at', 'kept_at', 'work_started_at', 'work_ended_at'] as $k) {
            $this->{$k} = $q->{$k}?->format('Y-m-d\TH:i');
        }
    }

    protected function rules(): array
    {
        return [
            'queue_type' => ['required', Rule::in(array_keys(ServiceQueue::queueTypes()))],
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'labour_id' => ['nullable', 'integer', Rule::exists('labours', 'id')],
            'technician_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'job_description' => ['nullable', 'string', 'max:255'],
            'ordering_mode' => ['required', Rule::in(array_keys(ServiceQueue::orderingModes()))],
            'screen_view' => ['required', Rule::in(array_keys(ServiceQueue::screenViews()))],
            'status' => ['required', Rule::in(array_keys(ServiceQueue::statuses()))],
            'is_high_priority' => ['boolean'],
            'high_priority_reason' => ['nullable', Rule::in(array_keys(ServiceQueue::highPriorityReasons())), Rule::requiredIf(fn () => $this->is_high_priority)],
            'hp_requested_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'hp_requested_to_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'rework_reason' => ['nullable', Rule::in(array_keys(ServiceQueue::reworkReasons()))],
            'delay_reason' => ['nullable', Rule::in(array_keys(ServiceQueue::delayReasons()))],
            'pause_reason' => ['nullable', Rule::in(array_keys(ServiceQueue::pauseReasons())), Rule::requiredIf(fn () => $this->status === ServiceQueue::STATUS_ON_HOLD)],
            'promised_delivery_at' => ['nullable', 'date'],
            'expected_completion_at' => ['nullable', 'date'],
            'kept_at' => ['nullable', 'date'],
            'work_started_at' => ['nullable', 'date'],
            'work_ended_at' => ['nullable', 'date', 'after_or_equal:work_started_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    #[Computed]
    public function labours()
    {
        return LabourMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
        $this->authorize($this->editingId ? 'queue_management.update' : 'queue_management.create');

        $data = $this->validate();

        if (isset($data['job_description']) && is_string($data['job_description'])) {
            $data['job_description'] = strtoupper($data['job_description']);
        }
        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }
        if (! $data['is_high_priority']) {
            $data['high_priority_reason'] = null;
            $data['hp_requested_by_id'] = null;
            $data['hp_requested_to_id'] = null;
            $data['ordering_mode'] = $data['ordering_mode'] === 'priority' ? 'fifo' : $data['ordering_mode'];
        }

        $isCreate = $this->editingId === null;

        if ($isCreate) {
            $q = ServiceQueue::create($data);
            $this->editingId = $q->id;
            $this->queue_no = $q->fresh()->queue_no;
        } else {
            $q = ServiceQueue::findOrFail($this->editingId);
            $q->update($data);
        }

        Flux::toast(text: 'Queue entry '.$q->fresh()->queue_no.($isCreate ? ' added.' : ' updated.'), variant: 'success');

        return redirect()->route('queue-management.index');
    }

    public function render()
    {
        return view('queue-management::edit');
    }
}
