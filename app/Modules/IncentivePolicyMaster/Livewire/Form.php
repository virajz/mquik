<?php

namespace App\Modules\IncentivePolicyMaster\Livewire;

use App\Modules\IncentivePolicyMaster\Models\IncentivePolicyMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public string $basis = 'labour_sales';

    public float $rate_percent = 0;

    public bool $is_active = true;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('incentive_policies', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('incentive_policies', 'code')->ignore($this->editingId),
            ],
            'basis' => ['required', Rule::in(array_keys(IncentivePolicyMaster::bases()))],
            'rate_percent' => ['numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('incentive-policy-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = IncentivePolicyMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->basis = $record->basis;
        $this->rate_percent = (float) $record->rate_percent;
        $this->is_active = $record->is_active;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'incentive_policy_master.update' : 'incentive_policy_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields (skip enums/flags).
        $skip = ['is_active', 'basis'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            IncentivePolicyMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Incentive Policy #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = IncentivePolicyMaster::create($data);
            Flux::toast(text: 'Incentive Policy #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('incentive-policy-master:saved');
        $this->resetForm();
        Flux::modal('incentive-policy-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->basis = 'labour_sales';
        $this->rate_percent = 0;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('incentive-policy-master::form');
    }
}
