<?php

namespace App\Modules\SmartSalary\Livewire;

use App\Modules\SmartSalary\Models\SmartSalary;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $key = '';

    public string $name = '';

    public string $category = 'Performance';

    public string $direction = SmartSalary::DIRECTION_HIGHER;

    public string $polarity = SmartSalary::POLARITY_POSITIVE;

    public ?string $unit = null;

    public float $weight = 0;

    public ?string $formula = null;

    public ?string $description = null;

    public bool $is_active = true;

    public int $sort_order = 0;

    #[On('smart-salary:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = SmartSalary::findOrFail($id);
        $this->editingId = $record->id;
        $this->key = $record->key;
        $this->name = $record->name;
        $this->category = $record->category;
        $this->direction = $record->direction;
        $this->polarity = $record->polarity;
        $this->unit = $record->unit;
        $this->weight = (float) $record->weight;
        $this->formula = $record->formula;
        $this->description = $record->description;
        $this->is_active = (bool) $record->is_active;
        $this->sort_order = (int) $record->sort_order;
    }

    protected function rules(): array
    {
        return [
            'key' => [
                'required', 'string', 'max:64', 'regex:/^[A-Z][A-Z0-9_]*$/',
                Rule::unique('smart_salary_kpis', 'key')->ignore($this->editingId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:40'],
            'direction' => ['required', Rule::in(array_keys(SmartSalary::directions()))],
            'polarity' => ['required', Rule::in(array_keys(SmartSalary::polarities()))],
            'unit' => ['nullable', 'string', 'max:20'],
            'weight' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'formula' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        // Capital typing for free-text strings (skip key — already upper, unit — symbol).
        foreach (['name', 'description', 'formula'] as $k) {
            if (filled($data[$k] ?? null)) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        if ($this->editingId) {
            SmartSalary::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'KPI '.$this->key.' updated.', variant: 'success');
        } else {
            SmartSalary::create($data);
            Flux::toast(text: 'KPI '.$this->key.' added.', variant: 'success');
        }

        $this->dispatch('smart-salary:saved');
        $this->resetForm();
        Flux::modal('smart-salary-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->key = '';
        $this->name = '';
        $this->category = 'Performance';
        $this->direction = SmartSalary::DIRECTION_HIGHER;
        $this->polarity = SmartSalary::POLARITY_POSITIVE;
        $this->unit = null;
        $this->weight = 0;
        $this->formula = null;
        $this->description = null;
        $this->is_active = true;
        $this->sort_order = 0;
    }

    public function render()
    {
        return view('smart-salary::form');
    }
}
