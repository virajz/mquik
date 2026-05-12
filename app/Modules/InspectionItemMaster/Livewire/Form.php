<?php

namespace App\Modules\InspectionItemMaster\Livewire;

use App\Concerns\HasQuickCreate;
use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    use HasQuickCreate;

    public ?int $editingId = null;

    public string $inspectionItemGroupSearch = '';

    public string $name = '';

    public ?string $code = null;

    public ?int $inspection_item_group_id = null;

    public string $check_type = 'visual';

    public ?string $measurement_unit = null;

    public bool $is_active = true;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('inspection_items', 'name')
                    ->where(fn ($q) => $q->where('inspection_item_group_id', $this->inspection_item_group_id))
                    ->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:30'],
            'inspection_item_group_id' => [
                'nullable', 'integer',
                Rule::exists('inspection_item_groups', 'id')->where('is_active', true),
            ],
            'check_type' => ['required', Rule::in(InspectionItemMaster::checkTypes())],
            'measurement_unit' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('inspection-item-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $r = InspectionItemMaster::findOrFail($id);
        $this->editingId = $r->id;
        $this->name = $r->name;
        $this->code = $r->code;
        $this->inspection_item_group_id = $r->inspection_item_group_id;
        $this->check_type = $r->check_type;
        $this->measurement_unit = $r->measurement_unit;
        $this->is_active = $r->is_active;
        $this->notes = $r->notes;
    }

    public function createInspectionItemGroup(): void
    {
        $this->quickCreate(
            modelClass: InspectionItemGroupMaster::class,
            targetProperty: 'inspection_item_group_id',
            searchProperty: 'inspectionItemGroupSearch',
            permission: 'inspection_item_group_master.create',
            label: 'Inspection item group',
        );
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'inspection_item_master.update' : 'inspection_item_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields except enums/ids/booleans/numbers.
        $skip = ['inspection_item_group_id', 'check_type', 'is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            InspectionItemMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Inspection item #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $r = InspectionItemMaster::create($data);
            Flux::toast(text: 'Inspection item #'.$r->id.' created.', variant: 'success');
        }

        $this->dispatch('inspection-item-master:saved');
        $this->resetForm();
        Flux::modal('inspection-item-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->inspection_item_group_id = null;
        $this->check_type = 'visual';
        $this->measurement_unit = null;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('inspection-item-master::form', [
            'groups' => InspectionItemGroupMaster::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'checkTypes' => InspectionItemMaster::checkTypes(),
        ]);
    }
}
