<?php

namespace App\Modules\RecommendationCategoryMaster\Livewire;

use App\Modules\RecommendationCategoryMaster\Models\RecommendationCategoryMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    /** Empty means this is a Category; set means a Sub Category of that one. */
    public ?int $parent_id = null;

    public bool $is_active = true;

    public int $sequence_no = 0;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255',
                // A sub category's name only has to be unique inside its parent.
                Rule::unique('recommendation_categories', 'name')
                    ->where('parent_id', $this->parent_id)
                    ->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('recommendation_categories', 'code')->ignore($this->editingId),
            ],
            'parent_id' => ['nullable', 'integer',
                Rule::exists('recommendation_categories', 'id')->whereNull('parent_id'),
            ],
            'is_active' => ['boolean'],
            'sequence_no' => ['required', 'integer', 'min:0', 'max:9999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** Only top-level rows can be a parent — categories nest one level, not many. */
    #[Computed]
    public function parents()
    {
        return RecommendationCategoryMaster::query()
            ->categories()
            ->where('is_active', true)
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->orderBy('sequence_no')->orderBy('name')
            ->get(['id', 'name']);
    }

    #[On('recommendation-category-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = RecommendationCategoryMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->parent_id = $record->parent_id;
        $this->is_active = $record->is_active;
        $this->sequence_no = (int) $record->sequence_no;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'recommendation_category_master.update' : 'recommendation_category_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields.
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            RecommendationCategoryMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Recommendation category #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = RecommendationCategoryMaster::create($data);
            Flux::toast(text: 'Recommendation category #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('recommendation-category-master:saved');
        $this->resetForm();
        Flux::modal('recommendation-category-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->parent_id = null;
        $this->is_active = true;
        $this->sequence_no = (int) (RecommendationCategoryMaster::max('sequence_no') ?? 0) + 1;
        $this->notes = null;
    }

    public function render()
    {
        return view('recommendation-category-master::form');
    }
}
