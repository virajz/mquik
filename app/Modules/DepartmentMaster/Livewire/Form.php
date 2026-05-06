<?php

namespace App\Modules\DepartmentMaster\Livewire;

use App\Modules\DepartmentMaster\Models\DepartmentMaster;
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
                Rule::unique('departments', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('departments', 'code')->ignore($this->editingId),
            ],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('department-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $r = DepartmentMaster::findOrFail($id);
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
            DepartmentMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Department #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $r = DepartmentMaster::create($data);
            Flux::toast(text: 'Department #'.$r->id.' created.', variant: 'success');
        }

        $this->dispatch('department-master:saved');
        $this->resetForm();
        Flux::modal('department-master-form')->close();
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
        return view('department-master::form');
    }
}
