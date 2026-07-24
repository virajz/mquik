<?php

namespace App\Modules\HsnMaster\Livewire;

use App\Modules\HsnMaster\Models\HsnMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public string $kind = HsnMaster::KIND_HSN;

    public ?string $gst_percent = null;

    public bool $is_active = true;

    public ?string $notes = null;

    /**
     * Validation rules — defined as a method (not #[Validate] attributes)
     * so we can use Rule::unique with the editing record's ID for updates.
     */
    protected function rules(): array
    {
        return [
            // Description need not be unique; the code is the identity.
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string',
                // GST codes are 4, 6 or 8 digits — nothing in between.
                'regex:/^([0-9]{4}|[0-9]{6}|[0-9]{8})$/',
                Rule::unique('hsn_codes', 'code')->ignore($this->editingId),
            ],
            'kind' => ['required', Rule::in(array_keys(HsnMaster::kinds()))],
            'gst_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return ['code.regex' => 'A GST code is 4, 6 or 8 digits.'];
    }

    #[On('hsn-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = HsnMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->kind = $record->kind;
        $this->gst_percent = $record->gst_percent === null ? null : (string) $record->gst_percent;
        $this->is_active = $record->is_active;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'hsn_master.update' : 'hsn_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields.
        $skip = ['is_active', 'kind', 'gst_percent'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            HsnMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'HSN / SAC Code #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = HsnMaster::create($data);
            Flux::toast(text: 'HSN / SAC Code #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('hsn-master:saved');
        $this->resetForm();
        Flux::modal('hsn-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->kind = HsnMaster::KIND_HSN;
        $this->gst_percent = null;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('hsn-master::form');
    }
}
