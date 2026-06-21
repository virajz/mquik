<?php

namespace App\Modules\JobStageMaster\Livewire;

use App\Modules\JobStageMaster\Models\JobStageMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public string $track = 'regular';

    public int $sort_order = 0;

    public bool $is_active = true;

    public ?string $notes = null;

    /**
     * Validation rules — defined as a method (not #[Validate] attributes)
     * so we can use Rule::unique with the editing record's ID for updates.
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('job_stages', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('job_stages', 'code')->ignore($this->editingId),
            ],
            'track' => ['required', Rule::in(array_keys(JobStageMaster::tracks()))],
            'sort_order' => ['integer', 'min:0', 'max:100000'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('job-stage-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = JobStageMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->track = $record->track;
        $this->sort_order = (int) $record->sort_order;
        $this->is_active = $record->is_active;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'job_stage_master.update' : 'job_stage_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields.
        $skip = ['is_active', 'track'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            JobStageMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Job stage #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = JobStageMaster::create($data);
            Flux::toast(text: 'Job stage #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('job-stage-master:saved');
        $this->resetForm();
        Flux::modal('job-stage-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->track = 'regular';
        $this->sort_order = 0;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('job-stage-master::form');
    }
}
