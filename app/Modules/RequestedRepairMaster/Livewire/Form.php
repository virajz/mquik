<?php

namespace App\Modules\RequestedRepairMaster\Livewire;

use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\RequestedRepairMaster\Models\RequestedRepairMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public bool $is_active = true;

    /** Frequent earns a checkbox on the job card; general lives in the picker. */
    public string $category = 'general';

    /** The heading a frequent complaint sits under on the job card. */
    public ?int $complaint_type_id = null;

    public ?string $notes = null;

    /**
     * Workshop departments this repair is offered in.
     *
     * Empty means every department — which is how every existing row behaves,
     * so leaving it blank never hides a repair.
     *
     * @var list<int>
     */
    public array $workshopDepartmentIds = [];

    /**
     * Validation rules — defined as a method (not #[Validate] attributes)
     * so we can use Rule::unique with the editing record's ID for updates.
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('requested_repairs', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('requested_repairs', 'code')->ignore($this->editingId),
            ],
            'is_active' => ['boolean'],
            'category' => ['required', Rule::in(RequestedRepairMaster::categories())],
            // Only a frequent complaint needs a heading to sit under.
            'complaint_type_id' => [
                Rule::requiredIf(fn () => $this->category === 'frequent'),
                'nullable', 'integer', Rule::exists('complaint_types', 'id')->where('is_active', true),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'workshopDepartmentIds' => ['array'],
            'workshopDepartmentIds.*' => ['integer', Rule::exists('workshop_departments', 'id')],
        ];
    }

    #[On('requested-repair-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = RequestedRepairMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->is_active = $record->is_active;
        $this->category = $record->category ?: 'general';
        $this->complaint_type_id = $record->complaint_type_id;
        $this->notes = $record->notes;
        $this->workshopDepartmentIds = $record->workshopDepartments()->pluck('workshop_departments.id')->all();
    }

    /** The headings a frequent complaint can sit under. */
    #[Computed]
    public function complaintTypes()
    {
        return ComplaintTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /** Active workshop departments for the picker. */
    #[Computed]
    public function workshopDepartments()
    {
        return WorkshopDepartmentMaster::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'requested_repair_master.update' : 'requested_repair_master.create');

        $data = $this->validate();
        $departmentIds = $data['workshopDepartmentIds'] ?? [];
        unset($data['workshopDepartmentIds']);

        // Workshop convention: capital typing on textual fields.
        $skip = ['is_active', 'category'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            $record = RequestedRepairMaster::findOrFail($this->editingId);
            $record->update($data);
            Flux::toast(text: 'Requested Repair #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = RequestedRepairMaster::create($data);
            Flux::toast(text: 'Requested Repair #'.$record->id.' created.', variant: 'success');
        }

        $record->workshopDepartments()->sync($departmentIds);

        $this->dispatch('requested-repair-master:saved');
        $this->resetForm();
        Flux::modal('requested-repair-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->category = 'general';
        $this->complaint_type_id = null;
        $this->code = null;
        $this->is_active = true;
        $this->notes = null;
        $this->workshopDepartmentIds = [];
    }

    public function render()
    {
        return view('requested-repair-master::form');
    }
}
