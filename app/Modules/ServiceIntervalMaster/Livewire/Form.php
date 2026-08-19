<?php

namespace App\Modules\ServiceIntervalMaster\Livewire;

use App\Modules\ServiceIntervalMaster\Models\ServiceIntervalMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?int $interval_months = null;

    public ?int $interval_km = null;

    public ?string $description = null;

    public bool $is_active = true;

    /**
     * Either bound may be blank — some jobs are time-based, some distance-based,
     * and a service with neither is simply never flagged as due.
     */
    protected function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('service_interval_masters', 'name')->ignore($this->editingId),
            ],
            'interval_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'interval_km' => ['nullable', 'integer', 'min:100', 'max:500000'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    #[On('service-interval-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = ServiceIntervalMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->interval_months = $record->interval_months;
        $this->interval_km = $record->interval_km;
        $this->description = $record->description;
        $this->is_active = (bool) $record->is_active;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'service_interval_master.update' : 'service_interval_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing for all string fields.
        foreach (['name', 'description'] as $key) {
            if (is_string($data[$key] ?? null)) {
                $data[$key] = strtoupper($data[$key]);
            }
        }

        if ($this->editingId) {
            ServiceIntervalMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Service interval #'.str_pad((string) $this->editingId, 5, '0', STR_PAD_LEFT).' updated.', variant: 'success');
        } else {
            $record = ServiceIntervalMaster::create($data);
            Flux::toast(text: 'Service interval #'.str_pad((string) $record->id, 5, '0', STR_PAD_LEFT).' created.', variant: 'success');
        }

        $this->dispatch('service-interval-master:saved');
        $this->resetForm();
        Flux::modal('service-interval-master-form')->close();
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'interval_months', 'interval_km', 'description', 'is_active']);
    }

    public function render()
    {
        return view('service-interval-master::form');
    }
}
