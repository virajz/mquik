<?php

namespace App\Modules\TaxMaster\Livewire;

use App\Modules\TaxMaster\Models\TaxMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $code = '';

    public ?string $hsn_sac = null;

    public string $gst_percent = '0.00';

    public string $cess_percent = '0.00';

    public bool $is_active = true;

    public ?string $notes = null;

    /**
     * Validation rules — defined as a method (not #[Validate] attributes)
     * so we can use Rule::unique with the editing record's ID for updates.
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20',
                Rule::unique('taxes', 'code')->ignore($this->editingId),
            ],
            'hsn_sac' => ['nullable', 'string', 'min:4', 'max:8'],
            'gst_percent' => ['required', 'numeric', 'min:0', 'max:50', 'decimal:0,2'],
            'cess_percent' => ['nullable', 'numeric', 'min:0', 'max:200', 'decimal:0,2'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('tax-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = TaxMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->hsn_sac = $record->hsn_sac;
        $this->gst_percent = (string) $record->gst_percent;
        $this->cess_percent = (string) $record->cess_percent;
        $this->is_active = $record->is_active;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $data = $this->validate();

        // Workshop convention: capital typing on textual fields. Skip numeric/boolean fields.
        $skip = ['gst_percent', 'cess_percent', 'is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if (! array_key_exists('cess_percent', $data) || $data['cess_percent'] === null || $data['cess_percent'] === '') {
            $data['cess_percent'] = '0.00';
        }

        if ($this->editingId) {
            TaxMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Tax #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = TaxMaster::create($data);
            Flux::toast(text: 'Tax #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('tax-master:saved');
        $this->resetForm();
        Flux::modal('tax-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = '';
        $this->hsn_sac = null;
        $this->gst_percent = '0.00';
        $this->cess_percent = '0.00';
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('tax-master::form');
    }
}
