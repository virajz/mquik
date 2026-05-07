<?php

namespace App\Modules\LocationMaster\Livewire;

use App\Modules\LocationMaster\Models\LocationMaster;
use App\Modules\RegionMaster\Models\RegionMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $code = '';

    public bool $is_head_office = false;

    public ?string $address = null;

    public ?int $city_id = null;

    public ?int $state_id = null;

    public ?string $pincode = null;

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $gstin = null;

    public bool $is_active = true;

    public ?string $notes = null;

    /**
     * Validation rules — defined as a method (not #[Validate] attributes)
     * so we can use Rule::unique with the editing record's ID for updates.
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique('locations', 'code')->ignore($this->editingId)],
            'is_head_office' => ['boolean'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city_id' => ['nullable', 'integer', Rule::exists('regions', 'id')->where('kind', 'city')],
            'state_id' => ['nullable', 'integer', Rule::exists('regions', 'id')->where('kind', 'state')],
            'pincode' => ['nullable', 'string', 'size:6'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'gstin' => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', Rule::unique('locations', 'gstin')->ignore($this->editingId)],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('location-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = LocationMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->is_head_office = $record->is_head_office;
        $this->address = $record->address;
        $this->city_id = $record->city_id;
        $this->state_id = $record->state_id;
        $this->pincode = $record->pincode;
        $this->phone = $record->phone;
        $this->email = $record->email;
        $this->gstin = $record->gstin;
        $this->is_active = $record->is_active;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $data = $this->validate();

        // Workshop convention: capital typing on textual fields. Skip FKs, contact fields, booleans, pincode.
        $skip = ['city_id', 'state_id', 'phone', 'email', 'pincode', 'is_head_office', 'is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            LocationMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Location #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = LocationMaster::create($data);
            Flux::toast(text: 'Location #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('location-master:saved');
        $this->resetForm();
        Flux::modal('location-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = '';
        $this->is_head_office = false;
        $this->address = null;
        $this->city_id = null;
        $this->state_id = null;
        $this->pincode = null;
        $this->phone = null;
        $this->email = null;
        $this->gstin = null;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('location-master::form', [
            'cities' => RegionMaster::query()
                ->where('kind', 'city')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'states' => RegionMaster::query()
                ->where('kind', 'state')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
