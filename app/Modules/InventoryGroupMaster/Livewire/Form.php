<?php

namespace App\Modules\InventoryGroupMaster\Livewire;

use App\Concerns\HasQuickCreate;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    use HasQuickCreate;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public ?int $parent_id = null;

    public string $parentSearch = '';

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
                Rule::unique('inventory_groups', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('inventory_groups', 'code')->ignore($this->editingId),
            ],
            'parent_id' => ['nullable', 'integer', 'exists:inventory_groups,id',
                // Cannot pick yourself as parent.
                function ($attribute, $value, $fail) {
                    if ($value !== null && $this->editingId !== null && (int) $value === (int) $this->editingId) {
                        $fail('A group cannot be its own parent.');
                    }
                },
            ],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Eligible parent groups — exclude self when editing.
     * (For MVP we don't recurse to exclude descendants; the rules() check covers self.)
     */
    #[Computed]
    public function parents()
    {
        return InventoryGroupMaster::query()
            ->when($this->editingId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[On('inventory-group-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = InventoryGroupMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->parent_id = $record->parent_id;
        $this->is_active = $record->is_active;
        $this->notes = $record->notes;
    }

    public function createParent(): void
    {
        $this->quickCreate(
            modelClass: InventoryGroupMaster::class,
            targetProperty: 'parent_id',
            searchProperty: 'parentSearch',
            permission: 'inventory_group_master.create',
            label: 'Parent group',
        );
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'inventory_group_master.update' : 'inventory_group_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields.
        $skip = ['is_active', 'parent_id'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            InventoryGroupMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Group #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = InventoryGroupMaster::create($data);
            Flux::toast(text: 'Group #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('inventory-group-master:saved');
        $this->resetForm();
        Flux::modal('inventory-group-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->parent_id = null;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('inventory-group-master::form');
    }
}
