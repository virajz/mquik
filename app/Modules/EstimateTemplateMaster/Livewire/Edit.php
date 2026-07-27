<?php

namespace App\Modules\EstimateTemplateMaster\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\EstimateTemplateMaster\Models\EstimateTemplateMaster;
use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Estimate Template')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    /** Search term for the server-backed inventoryGroups picker. */
    public string $inventoryGroupSearch = '';

    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public ?string $effective_date = null;

    public ?string $category = null;

    public ?int $inventory_group_id = null;

    public ?int $vehicle_brand_id = null;

    public ?int $vehicle_model_id = null;

    public ?int $vehicle_variant_id = null;

    public ?int $service_package_id = null;

    public bool $is_active = true;

    public ?string $notes = null;

    public ?string $brochure_path = null;

    public ?string $brochure_name = null;

    /** Freshly uploaded brochure, if any. */
    public $brochureFile = null;

    /** @var list<array<string, mixed>> */
    public array $items = [];

    public function mount(?EstimateTemplateMaster $estimateTemplate = null): void
    {
        if ($estimateTemplate && $estimateTemplate->exists) {
            $this->load($estimateTemplate);
        }
    }

    protected function load(EstimateTemplateMaster $t): void
    {
        $t->load('items');
        $this->editingId = $t->id;
        $this->name = $t->name;
        $this->code = $t->code;
        $this->effective_date = $t->effective_date?->format('Y-m-d');
        $this->category = $t->category;
        $this->inventory_group_id = $t->inventory_group_id;
        $this->vehicle_brand_id = $t->vehicle_brand_id;
        $this->vehicle_model_id = $t->vehicle_model_id;
        $this->vehicle_variant_id = $t->vehicle_variant_id;
        $this->service_package_id = $t->service_package_id;
        $this->is_active = (bool) $t->is_active;
        $this->notes = $t->notes;
        $this->brochure_path = $t->brochure_path;
        $this->brochure_name = $t->brochure_name;

        $this->items = $t->items->map(fn ($i) => [
            'id' => $i->id,
            'line_type' => $i->line_type,
            'spare_id' => $i->spare_id,
            'labour_id' => $i->labour_id,
            'inventory_group_id' => $i->inventory_group_id,
            'uom_id' => $i->uom_id,
            'hsn_id' => $i->hsn_id,
            'tax_id' => $i->tax_id,
            'description' => $i->description,
            'default_qty' => (float) $i->default_qty,
            'unit_rate' => $i->unit_rate,
            'sequence_no' => (int) $i->sequence_no,
        ])->all();
    }

    public function addItem(string $type): void
    {
        $this->items[] = [
            'id' => null,
            'line_type' => $type,
            'spare_id' => null,
            'labour_id' => null,
            'inventory_group_id' => null,
            'uom_id' => null,
            'hsn_id' => null,
            'tax_id' => null,
            'description' => null,
            'default_qty' => 1,
            'unit_rate' => null,
            'sequence_no' => count($this->items) + 1,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function clearBrochure(): void
    {
        if ($this->brochure_path) {
            Storage::disk('public')->delete($this->brochure_path);
        }
        $this->brochure_path = null;
        $this->brochure_name = null;
        $this->brochureFile = null;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('estimate_templates', 'name')->ignore($this->editingId)],
            'code' => ['nullable', 'string', 'max:32', Rule::unique('estimate_templates', 'code')->ignore($this->editingId)],
            'effective_date' => ['nullable', 'date'],
            'category' => ['nullable', Rule::in(array_keys(EstimateTemplateMaster::categories()))],
            'inventory_group_id' => ['nullable', 'integer', 'exists:inventory_groups,id'],
            'vehicle_brand_id' => ['nullable', 'integer', 'exists:vehicle_brands,id'],
            'vehicle_model_id' => ['nullable', 'integer', 'exists:vehicle_models,id'],
            'vehicle_variant_id' => ['nullable', 'integer', 'exists:vehicle_variants,id'],
            'service_package_id' => ['nullable', 'integer', 'exists:service_packages,id'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'brochureFile' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
            'items' => ['array'],
            'items.*.line_type' => ['required', 'in:spare,labour'],
            'items.*.spare_id' => ['nullable', 'integer', 'exists:spares,id', 'required_if:items.*.line_type,spare'],
            'items.*.labour_id' => ['nullable', 'integer', 'exists:labours,id', 'required_if:items.*.line_type,labour'],
            'items.*.inventory_group_id' => ['nullable', 'integer', 'exists:inventory_groups,id'],
            'items.*.uom_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'items.*.hsn_id' => ['nullable', 'integer', 'exists:hsn_codes,id'],
            'items.*.tax_id' => ['nullable', 'integer', 'exists:taxes,id'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.default_qty' => ['numeric', 'min:0.01'],
            'items.*.unit_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    #[Computed]
    public function inventoryGroups()
    {
        return $this->pickerOptions(
            query: InventoryGroupMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'code'],
            term: $this->inventoryGroupSearch,
            selected: $this->inventory_group_id,
            columns: ['id', 'name', 'parent_id'],
            limit: 30,
        );
    }

    #[Computed]
    public function inventoryGroupOptions()
    {
        return InventoryGroupMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function spares()
    {
        return SpareMaster::query()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name']);
    }

    #[Computed]
    public function labours()
    {
        return LabourMaster::query()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name']);
    }

    #[Computed]
    public function vehicleBrands()
    {
        return VehicleBrandMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function vehicleModels()
    {
        return VehicleModelMaster::query()
            ->when($this->vehicle_brand_id, fn ($q) => $q->where('brand_id', $this->vehicle_brand_id))
            ->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name']);
    }

    #[Computed]
    public function vehicleVariants()
    {
        return VehicleVariantMaster::query()
            ->when($this->vehicle_model_id, fn ($q) => $q->where('model_id', $this->vehicle_model_id))
            ->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name']);
    }

    #[Computed]
    public function servicePackages()
    {
        return ServicePackageMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function uoms()
    {
        return UnitOfMeasureMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
    }

    #[Computed]
    public function taxes()
    {
        return TaxMaster::query()->where('is_active', true)->orderBy('gst_percent')->get(['id', 'name', 'gst_percent']);
    }

    #[Computed]
    public function hsnCodes()
    {
        return HsnMaster::query()->where('is_active', true)->orderBy('code')->limit(500)->get(['id', 'code']);
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'estimate_template_master.update' : 'estimate_template_master.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        unset($data['items'], $data['brochureFile']);

        foreach (['name', 'code', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        if ($this->brochureFile instanceof TemporaryUploadedFile) {
            if ($this->brochure_path) {
                Storage::disk('public')->delete($this->brochure_path);
            }
            $this->brochure_path = $this->brochureFile->store('estimate-templates/brochures', 'public');
            $this->brochure_name = $this->brochureFile->getClientOriginalName();
        }
        $data['brochure_path'] = $this->brochure_path;
        $data['brochure_name'] = $this->brochure_name;

        $isCreate = $this->editingId === null;

        DB::transaction(function () use ($data, $items) {
            if ($this->editingId) {
                $t = EstimateTemplateMaster::findOrFail($this->editingId);
                $t->update($data);
            } else {
                $t = EstimateTemplateMaster::create($data);
                $this->editingId = $t->id;
            }

            $keptIds = [];
            foreach (array_values($items) as $i => $row) {
                $payload = [
                    'line_type' => $row['line_type'],
                    'spare_id' => $row['line_type'] === 'spare' ? ($row['spare_id'] ?? null) : null,
                    'labour_id' => $row['line_type'] === 'labour' ? ($row['labour_id'] ?? null) : null,
                    'inventory_group_id' => $row['inventory_group_id'] ?: null,
                    'uom_id' => $row['uom_id'] ?: null,
                    'hsn_id' => $row['hsn_id'] ?: null,
                    'tax_id' => $row['tax_id'] ?: null,
                    'description' => isset($row['description']) && is_string($row['description']) ? strtoupper($row['description']) : null,
                    'default_qty' => $row['default_qty'] ?? 1,
                    'unit_rate' => ($row['unit_rate'] ?? '') !== '' ? $row['unit_rate'] : null,
                    'sequence_no' => $i + 1,
                ];
                if (! empty($this->items[$i]['id'])) {
                    $existing = $t->items()->whereKey($this->items[$i]['id'])->first();
                    if ($existing) {
                        $existing->update($payload);
                        $keptIds[] = $existing->id;

                        continue;
                    }
                }
                $created = $t->items()->create($payload);
                $keptIds[] = $created->id;
            }
            $t->items()->whereNotIn('id', $keptIds)->delete();
        });

        Flux::toast(text: 'Estimate template '.($isCreate ? 'created.' : 'updated.'), variant: 'success');

        return redirect()->route('estimate-template-master.index');
    }

    public function render()
    {
        return view('estimate-template-master::edit');
    }
}
