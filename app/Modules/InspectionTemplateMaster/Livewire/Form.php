<?php

namespace App\Modules\InspectionTemplateMaster\Livewire;

use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public string $applies_to = 'custom';

    /** @var array<int, int> Item IDs selected for this template, in display order. */
    public array $selected_item_ids = [];

    public bool $is_active = true;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('inspection_templates', 'name')->ignore($this->editingId),
            ],
            'code' => [
                'nullable', 'string', 'max:30',
                Rule::unique('inspection_templates', 'code')->ignore($this->editingId),
            ],
            'applies_to' => ['required', Rule::in(InspectionTemplateMaster::appliesToOptions())],
            'selected_item_ids' => ['nullable', 'array'],
            'selected_item_ids.*' => ['integer', Rule::exists('inspection_items', 'id')],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('inspection-template-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $r = InspectionTemplateMaster::findOrFail($id);
        $this->editingId = $r->id;
        $this->name = $r->name;
        $this->code = $r->code;
        $this->applies_to = $r->applies_to;
        $this->is_active = $r->is_active;
        $this->notes = $r->notes;
        $this->selected_item_ids = $r->items()
            ->orderBy('inspection_template_items.position')
            ->pluck('inspection_items.id')
            ->all();
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'inspection_template_master.update' : 'inspection_template_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields except enums/ids/booleans/numbers.
        $skip = ['applies_to', 'selected_item_ids', 'is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        $itemIds = $data['selected_item_ids'] ?? [];
        unset($data['selected_item_ids']);

        if ($this->editingId) {
            $template = InspectionTemplateMaster::findOrFail($this->editingId);
            $template->update($data);
            Flux::toast(text: 'Inspection template #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $template = InspectionTemplateMaster::create($data);
            Flux::toast(text: 'Inspection template #'.$template->id.' created.', variant: 'success');
        }

        $sync = collect($itemIds)
            ->values()
            ->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i + 1, 'is_required' => true]])
            ->all();

        $template->items()->sync($sync);

        $this->dispatch('inspection-template-master:saved');
        $this->resetForm();
        Flux::modal('inspection-template-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->applies_to = 'custom';
        $this->selected_item_ids = [];
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('inspection-template-master::form', [
            'appliesToOptions' => InspectionTemplateMaster::appliesToOptions(),
            'availableItems' => InspectionItemMaster::query()
                ->with('group:id,name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'inspection_item_group_id']),
        ]);
    }
}
