<?php

namespace App\Modules\RecommendationDescriptionMaster\Livewire;

use App\Modules\RecommendationCategoryMaster\Models\RecommendationCategoryMaster;
use App\Modules\RecommendationDescriptionMaster\Models\RecommendationDescriptionMaster;
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

    public ?int $category_id = null;

    public ?int $sub_category_id = null;

    public bool $is_active = true;

    public int $sequence_no = 0;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('recommendation_descriptions', 'name')
                    ->where('category_id', $this->category_id)
                    ->where('sub_category_id', $this->sub_category_id)
                    ->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('recommendation_descriptions', 'code')->ignore($this->editingId),
            ],
            'category_id' => ['required', 'integer',
                Rule::exists('recommendation_categories', 'id')->whereNull('parent_id'),
            ],
            'sub_category_id' => ['nullable', 'integer',
                // Must actually belong to the chosen category, or the filing lies.
                Rule::exists('recommendation_categories', 'id')->where('parent_id', $this->category_id),
            ],
            'is_active' => ['boolean'],
            'sequence_no' => ['required', 'integer', 'min:0', 'max:9999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[Computed]
    public function categories()
    {
        return RecommendationCategoryMaster::query()
            ->categories()->where('is_active', true)
            ->orderBy('sequence_no')->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function subCategories()
    {
        if (! $this->category_id) {
            return collect();
        }

        return RecommendationCategoryMaster::query()
            ->subCategories($this->category_id)->where('is_active', true)
            ->orderBy('sequence_no')->orderBy('name')
            ->get(['id', 'name']);
    }

    /** Changing the category invalidates whatever sub category was chosen under the old one. */
    public function updatedCategoryId(): void
    {
        $this->sub_category_id = null;
        unset($this->subCategories);
    }

    #[On('recommendation-description-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = RecommendationDescriptionMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->category_id = $record->category_id;
        $this->sub_category_id = $record->sub_category_id;
        $this->is_active = $record->is_active;
        $this->sequence_no = (int) $record->sequence_no;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'recommendation_description_master.update' : 'recommendation_description_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing for all string fields.
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            RecommendationDescriptionMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Recommendation description #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = RecommendationDescriptionMaster::create($data);
            Flux::toast(text: 'Recommendation description #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('recommendation-description-master:saved');
        $this->resetForm();
        Flux::modal('recommendation-description-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->category_id = null;
        $this->sub_category_id = null;
        $this->is_active = true;
        $this->sequence_no = (int) (RecommendationDescriptionMaster::max('sequence_no') ?? 0) + 1;
        $this->notes = null;
    }

    public function render()
    {
        return view('recommendation-description-master::form');
    }
}
