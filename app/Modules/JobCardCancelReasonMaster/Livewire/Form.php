<?php

namespace App\Modules\JobCardCancelReasonMaster\Livewire;

use App\Modules\JobCardCancelReasonMaster\Models\JobCardCancelReasonMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public bool $is_active = true;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('job_card_cancel_reasons', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('job_card_cancel_reasons', 'code')->ignore($this->editingId),
            ],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('job-card-cancel-reason-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $r = JobCardCancelReasonMaster::findOrFail($id);
        $this->editingId = $r->id;
        $this->name = $r->name;
        $this->code = $r->code;
        $this->is_active = $r->is_active;
        $this->notes = $r->notes;
    }

    public function save(): void
    {
        $data = $this->validate();

        $skip = ['is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            JobCardCancelReasonMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Cancel Reason #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $r = JobCardCancelReasonMaster::create($data);
            Flux::toast(text: 'Cancel Reason #'.$r->id.' created.', variant: 'success');
        }

        $this->dispatch('job-card-cancel-reason-master:saved');
        $this->resetForm();
        Flux::modal('job-card-cancel-reason-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('job-card-cancel-reason-master::form');
    }
}
