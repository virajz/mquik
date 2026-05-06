<?php

namespace App\Modules\JobDescriptionMaster\Livewire;

use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public string $category = 'general';

    public ?int $service_type_id = null;

    public ?string $standard_hours = null;

    public bool $is_active = true;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('job_descriptions', 'name')
                    ->where(fn ($q) => $q->where('service_type_id', $this->service_type_id))
                    ->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20'],
            'category' => ['required', Rule::in(JobDescriptionMaster::categories())],
            'service_type_id' => [
                'nullable', 'integer',
                Rule::exists('service_types', 'id')->where('is_active', true),
            ],
            'standard_hours' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('job-description-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $r = JobDescriptionMaster::findOrFail($id);
        $this->editingId = $r->id;
        $this->name = $r->name;
        $this->code = $r->code;
        $this->category = $r->category;
        $this->service_type_id = $r->service_type_id;
        $this->standard_hours = $r->standard_hours !== null ? (string) $r->standard_hours : null;
        $this->is_active = $r->is_active;
        $this->notes = $r->notes;
    }

    public function save(): void
    {
        $data = $this->validate();

        $skip = ['category', 'service_type_id', 'standard_hours', 'is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            JobDescriptionMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Job description #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $r = JobDescriptionMaster::create($data);
            Flux::toast(text: 'Job description #'.$r->id.' created.', variant: 'success');
        }

        $this->dispatch('job-description-master:saved');
        $this->resetForm();
        Flux::modal('job-description-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->category = 'general';
        $this->service_type_id = null;
        $this->standard_hours = null;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('job-description-master::form', [
            'serviceTypes' => ServiceTypeMaster::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'categories' => JobDescriptionMaster::categories(),
        ]);
    }
}
