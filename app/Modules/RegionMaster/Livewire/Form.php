<?php

namespace App\Modules\RegionMaster\Livewire;

use App\Modules\RegionMaster\Models\RegionMaster;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $kind = 'state';

    public string $name = '';

    public ?string $code = null;

    public ?int $parent_id = null;

    public bool $is_active = true;

    public ?string $notes = null;

    /**
     * Map a kind to the kind of its parent.
     */
    protected function parentKindFor(string $kind): ?string
    {
        return match ($kind) {
            'state' => null,
            'city' => 'state',
            'area' => 'city',
            'pincode' => 'area',
            default => null,
        };
    }

    /**
     * Validation rules — defined as a method so we can inspect $this->kind.
     */
    protected function rules(): array
    {
        $kindList = implode(',', RegionMaster::kinds());
        $parentKind = $this->parentKindFor($this->kind);

        return [
            'kind' => ['required', "in:{$kindList}"],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20'],
            'parent_id' => [
                $parentKind === null ? 'nullable' : 'required',
                'integer',
                'exists:regions,id',
                // Custom: parent must be of the correct kind.
                function ($attribute, $value, $fail) use ($parentKind) {
                    if ($parentKind === null) {
                        if ($value !== null) {
                            $fail('A state cannot have a parent.');
                        }

                        return;
                    }

                    if ($value === null) {
                        return; // 'required' rule above will catch.
                    }

                    $parent = RegionMaster::find($value);
                    if (! $parent) {
                        $fail('Selected parent does not exist.');

                        return;
                    }
                    if ($parent->kind !== $parentKind) {
                        $fail("Parent must be a {$parentKind}, got a {$parent->kind}.");
                    }
                    if ($this->editingId !== null && (int) $value === (int) $this->editingId) {
                        $fail('A region cannot be its own parent.');
                    }
                },
            ],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Reset parent when kind changes (live).
     */
    public function updatedKind(): void
    {
        $this->parent_id = null;
    }

    /**
     * Eligible parent options based on current kind.
     */
    #[Computed]
    public function parentOptions()
    {
        $parentKind = $this->parentKindFor($this->kind);
        if ($parentKind === null) {
            return collect();
        }

        return RegionMaster::query()
            ->where('kind', $parentKind)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[On('region-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = RegionMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->kind = $record->kind;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->parent_id = $record->parent_id;
        $this->is_active = $record->is_active;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $data = $this->validate();

        // Workshop convention: capital typing on textual fields.
        // Skip kind (lowercase enum), parent_id (FK), is_active (bool).
        $skip = ['kind', 'is_active', 'parent_id'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            RegionMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Region #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = RegionMaster::create($data);
            Flux::toast(text: 'Region #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('region-master:saved');
        $this->resetForm();
        Flux::modal('region-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->kind = 'state';
        $this->name = '';
        $this->code = null;
        $this->parent_id = null;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('region-master::form');
    }
}
