<?php

namespace App\Modules\LabourMaster\Livewire;

use App\Concerns\HasQuickCreate;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\VehicleSegmentMaster\Models\VehicleSegmentMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
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

    public ?string $labour_code = null;

    public ?string $description = null;

    public ?string $hsn_sac_code = null;

    public ?int $vehicle_segment_id = null;

    public string $vehicleSegmentSearch = '';

    public ?int $tax_id = null;

    public ?int $workshop_department_id = null;

    public ?int $inventory_group_id = null;

    public ?int $inventory_sub_group_id = null;

    public float $rate_before_tax = 0;

    public bool $is_osl = false;

    public ?string $remark = null;

    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'labour_code' => ['nullable', 'string', 'max:64', Rule::unique('labours', 'labour_code')->ignore($this->editingId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'hsn_sac_code' => ['nullable', 'string', 'max:16'],
            'vehicle_segment_id' => ['nullable', 'integer', Rule::exists('vehicle_segments', 'id')->where('is_active', true)],
            'tax_id' => ['nullable', 'integer', Rule::exists('taxes', 'id')->where('is_active', true)],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')->where('is_active', true)],
            'inventory_group_id' => ['nullable', 'integer', Rule::exists('inventory_groups', 'id')->where('is_active', true)->whereNull('parent_id')],
            'inventory_sub_group_id' => ['nullable', 'integer', Rule::exists('inventory_groups', 'id')->where('is_active', true)],
            'rate_before_tax' => ['numeric', 'min:0', 'max:9999999.99'],
            'is_osl' => ['boolean'],
            'remark' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function updatedInventoryGroupId(): void
    {
        $this->inventory_sub_group_id = null;
    }

    #[On('labour-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $r = LabourMaster::findOrFail($id);
        $this->editingId = $r->id;
        foreach (['name', 'labour_code', 'description', 'hsn_sac_code', 'remark'] as $k) {
            $this->{$k} = $r->{$k};
        }
        $this->vehicle_segment_id = $r->vehicle_segment_id;
        $this->tax_id = $r->tax_id;
        $this->workshop_department_id = $r->workshop_department_id;
        $this->inventory_group_id = $r->inventory_group_id;
        $this->inventory_sub_group_id = $r->inventory_sub_group_id;
        $this->rate_before_tax = (float) $r->rate_before_tax;
        $this->is_osl = (bool) $r->is_osl;
        $this->is_active = (bool) $r->is_active;
    }

    public function createSegment(): void
    {
        $this->quickCreate(
            modelClass: VehicleSegmentMaster::class,
            targetProperty: 'vehicle_segment_id',
            searchProperty: 'vehicleSegmentSearch',
            permission: 'vehicle_segment_master.create',
            label: 'Vehicle segment',
        );
    }

    #[Computed]
    public function segments()
    {
        return VehicleSegmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function taxes()
    {
        return TaxMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'gst_percent', 'cess_percent']);
    }

    #[Computed]
    public function workshopDepartments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function parentInventoryGroups()
    {
        return InventoryGroupMaster::query()->where('is_active', true)->whereNull('parent_id')->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function inventorySubGroups()
    {
        if (! $this->inventory_group_id) {
            return collect();
        }

        return InventoryGroupMaster::query()
            ->where('is_active', true)
            ->where('parent_id', $this->inventory_group_id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function rateInclTax(): float
    {
        $tax = $this->tax_id ? TaxMaster::find($this->tax_id) : null;
        $pct = (float) (($tax?->gst_percent ?? 0) + ($tax?->cess_percent ?? 0));

        return round($this->rate_before_tax * (1 + $pct / 100), 2);
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'labour_master.update' : 'labour_master.create');

        $data = $this->validate();

        $skip = ['rate_before_tax', 'is_active', 'is_osl', 'vehicle_segment_id', 'tax_id', 'workshop_department_id', 'inventory_group_id', 'inventory_sub_group_id'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            LabourMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Labour #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $r = LabourMaster::create($data);
            Flux::toast(text: 'Labour #'.$r->id.' created.', variant: 'success');
        }

        $this->dispatch('labour-master:saved');
        $this->resetForm();
        Flux::modal('labour-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->labour_code = null;
        $this->description = null;
        $this->hsn_sac_code = null;
        $this->vehicle_segment_id = null;
        $this->tax_id = null;
        $this->workshop_department_id = null;
        $this->inventory_group_id = null;
        $this->inventory_sub_group_id = null;
        $this->rate_before_tax = 0;
        $this->is_osl = false;
        $this->remark = null;
        $this->is_active = true;
    }

    public function render()
    {
        return view('labour-master::form');
    }
}
