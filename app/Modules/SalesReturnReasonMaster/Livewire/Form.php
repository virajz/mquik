<?php

namespace App\Modules\SalesReturnReasonMaster\Livewire;

use App\Modules\SalesReturnReasonMaster\Models\SalesReturnReasonMaster;
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

    /**
     * Validation rules — defined as a method (not #[Validate] attributes)
     * so we can use Rule::unique with the editing record's ID for updates.
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('sales_return_reasons', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('sales_return_reasons', 'code')->ignore($this->editingId),
            ],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('sales-return-reason-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = SalesReturnReasonMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->is_active = $record->is_active;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'sales_return_reason_master.update' : 'sales_return_reason_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields.
        $skip = ['is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            SalesReturnReasonMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Sales Return Reason #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = SalesReturnReasonMaster::create($data);
            Flux::toast(text: 'Sales Return Reason #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('sales-return-reason-master:saved');
        $this->resetForm();
        Flux::modal('sales-return-reason-master-form')->close();
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
        return view('sales-return-reason-master::form');
    }
}
