<?php

namespace App\Modules\PhotoTypeMaster\Livewire;

use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public string $group = 'GENERAL';

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
                Rule::unique('photo_types', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('photo_types', 'code')->ignore($this->editingId),
            ],
            'group' => ['required', 'string', 'max:60'],
            'sort_order' => ['integer', 'min:0', 'max:100000'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('photo-type-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = PhotoTypeMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->group = $record->group;
        $this->sort_order = (int) $record->sort_order;
        $this->is_active = $record->is_active;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'photo_type_master.update' : 'photo_type_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields.
        $skip = ['is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            PhotoTypeMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Photo Type #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = PhotoTypeMaster::create($data);
            Flux::toast(text: 'Photo Type #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('photo-type-master:saved');
        $this->resetForm();
        Flux::modal('photo-type-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->group = 'GENERAL';
        $this->sort_order = 0;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('photo-type-master::form');
    }
}
