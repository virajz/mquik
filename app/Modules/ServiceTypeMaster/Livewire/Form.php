<?php

namespace App\Modules\ServiceTypeMaster\Livewire;

use App\Concerns\HasQuickCreate;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    use HasQuickCreate;

    public ?int $editingId = null;

    public string $workshopDepartmentSearch = '';

    public string $name = '';

    public ?string $code = null;

    public ?int $workshop_department_id = null;

    public bool $requires_advisor = true;

    /** Marks the type as insurance work, which is what makes an insurer mandatory. */
    public bool $is_insurance = false;

    public bool $is_active = true;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('service_types', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('service_types', 'code')->ignore($this->editingId),
            ],
            'workshop_department_id' => ['required', 'integer',
                Rule::exists('workshop_departments', 'id')->where('is_active', true),
            ],
            'requires_advisor' => ['boolean'],
            'is_insurance' => ['boolean'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('service-type-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $r = ServiceTypeMaster::findOrFail($id);
        $this->editingId = $r->id;
        $this->name = $r->name;
        $this->code = $r->code;
        $this->workshop_department_id = $r->workshop_department_id;
        $this->requires_advisor = $r->requires_advisor;
        $this->is_insurance = (bool) $r->is_insurance;
        $this->is_active = $r->is_active;
        $this->notes = $r->notes;
    }

    public function createWorkshopDepartment(): void
    {
        $this->quickCreate(
            modelClass: WorkshopDepartmentMaster::class,
            targetProperty: 'workshop_department_id',
            searchProperty: 'workshopDepartmentSearch',
            permission: 'workshop_department_master.create',
            label: 'Workshop department',
        );
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'service_type_master.update' : 'service_type_master.create');

        $data = $this->validate();

        $skip = ['workshop_department_id', 'requires_advisor', 'is_insurance', 'is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            ServiceTypeMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Service type #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $r = ServiceTypeMaster::create($data);
            Flux::toast(text: 'Service type #'.$r->id.' created.', variant: 'success');
        }

        $this->dispatch('service-type-master:saved');
        $this->resetForm();
        Flux::modal('service-type-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->workshop_department_id = null;
        $this->requires_advisor = true;
        $this->is_insurance = false;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('service-type-master::form', [
            'departments' => WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
