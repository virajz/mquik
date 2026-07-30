<?php

namespace App\Modules\SpareMaster\Livewire;

use App\Concerns\HasQuickCreate;
use App\Concerns\SearchesPickerOptions;
use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\PartTypeMaster\Models\PartTypeMaster;
use App\Modules\RackMaster\Models\RackMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Modules\SpareMaster\Models\SpareAttachment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Spare')]
class Edit extends Component
{
    use HasQuickCreate;
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $spare_code = null;

    public ?string $description = null;

    public ?int $hsn_id = null;

    public ?int $spare_brand_id = null;

    public string $spareBrandSearch = '';

    public ?int $part_type_id = null;

    public ?int $rack_id = null;

    /** @var list<int> */
    public ?int $tax_id = null;

    public ?int $inventory_group_id = null;

    public ?int $inventory_sub_group_id = null;

    public ?string $inventory_type = null;

    public ?int $workshop_department_id = null;

    public ?int $uom_id = null;

    public string $uomSearch = '';

    public float $rate_before_tax = 0;

    /** Printed MRP (tax-inclusive) — what the customer sees on the box. */
    public ?float $mrp = null;

    /** Which figure the user typed last: 'mrp' or 'rate'. Not persisted. */
    public string $priceBasis = 'mrp';

    /** Search terms for the server-backed pickers. */
    public string $variantSearch = '';

    public float $min_qty = 0;

    public float $max_qty = 0;

    public ?string $barcode_type = null;

    public ?string $location = null;

    public string $spare_type = SpareMaster::TYPE_VEHICLE_SPECIFIC;

    public ?string $tyre_dimension = null;

    public ?string $rim_size = null;

    public ?string $load_speed_index = null;

    public ?string $tread_pattern = null;

    public ?string $remark = null;

    public bool $is_active = true;

    /** @var list<int> */
    public array $variant_ids = [];

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?SpareMaster $spare = null): void
    {
        if ($spare && $spare->exists) {
            $this->load($spare);
        }
    }

    protected function load(SpareMaster $spare): void
    {
        $spare->load(['vehicleVariants:id', 'attachments']);

        $this->editingId = $spare->id;
        $this->inventory_type = $spare->inventory_type;
        $this->part_type_id = $spare->part_type_id;
        $this->hsn_id = $spare->hsn_id;
        $this->rack_id = $spare->rack_id;
        foreach (['name', 'spare_code', 'description', 'barcode_type', 'location', 'tyre_dimension', 'rim_size', 'load_speed_index', 'tread_pattern', 'remark'] as $k) {
            $this->{$k} = $spare->{$k};
        }
        $this->spare_brand_id = $spare->spare_brand_id;
        $this->tax_id = $spare->tax_id;
        $this->inventory_group_id = $spare->inventory_group_id;
        $this->inventory_sub_group_id = $spare->inventory_sub_group_id;
        $this->workshop_department_id = $spare->workshop_department_id;
        $this->uom_id = $spare->uom_id;
        $this->rate_before_tax = (float) $spare->rate_before_tax;
        $this->mrp = $spare->mrp === null ? null : (float) $spare->mrp;
        $this->min_qty = (float) $spare->min_qty;
        $this->max_qty = (float) $spare->max_qty;
        $this->spare_type = $spare->spare_type ?? SpareMaster::TYPE_VEHICLE_SPECIFIC;
        $this->is_active = (bool) $spare->is_active;
        $this->variant_ids = $spare->vehicleVariants->pluck('id')->all();

        $this->attachments = $spare->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'spare_code' => ['nullable', 'string', 'max:64', Rule::unique('spares', 'spare_code')->ignore($this->editingId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'hsn_id' => ['nullable', 'integer', Rule::exists('hsn_codes', 'id')->where('is_active', true)],
            'spare_brand_id' => ['nullable', 'integer', Rule::exists('spare_brands', 'id')->where('is_active', true)],
            'part_type_id' => ['nullable', 'integer', Rule::exists('part_types', 'id')->where('is_active', true)],
            'rack_id' => ['nullable', 'integer', Rule::exists('racks', 'id')->where('is_active', true)],
            'tax_id' => ['nullable', 'integer', Rule::exists('taxes', 'id')->where('is_active', true)],
            'inventory_group_id' => ['nullable', 'integer', Rule::exists('inventory_groups', 'id')->where('is_active', true)->whereNull('parent_id')],
            'inventory_sub_group_id' => ['nullable', 'integer', Rule::exists('inventory_groups', 'id')->where('is_active', true)],
            'inventory_type' => ['nullable', Rule::in(array_keys(SpareMaster::inventoryTypes()))],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')->where('is_active', true)],
            'uom_id' => ['nullable', 'integer', Rule::exists('units_of_measure', 'id')->where('is_active', true)],
            'rate_before_tax' => ['numeric', 'min:0', 'max:9999999.99'],
            'mrp' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'min_qty' => ['numeric', 'min:0', 'max:9999999.99'],
            'max_qty' => ['numeric', 'min:0', 'max:9999999.99', 'gte:min_qty'],
            'barcode_type' => ['nullable', 'string', 'in:EAN-13,CODE-128,QR'],
            'location' => ['nullable', 'string', 'max:64'],
            'spare_type' => ['required', Rule::in(array_keys(SpareMaster::spareTypes()))],
            'tyre_dimension' => ['nullable', 'string', 'max:32', 'required_if:spare_type,'.SpareMaster::TYPE_TYRE],
            'rim_size' => ['nullable', 'string', 'max:16', 'required_if:spare_type,'.SpareMaster::TYPE_TYRE],
            'load_speed_index' => ['nullable', 'string', 'max:16'],
            'tread_pattern' => ['nullable', 'string', 'max:32'],
            'remark' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'variant_ids' => ['array'],
            'variant_ids.*' => ['integer', Rule::exists('vehicle_variants', 'id')->where('is_active', true)],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(SpareAttachment::attachmentTypes()))],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ];
    }

    public function addAttachment(): void
    {
        $this->attachments[] = ['id' => null, 'attachment_type' => null, 'path' => null, 'original_name' => null, 'notes' => null];
    }

    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index], $this->attachmentFiles[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function updatedInventoryGroupId(): void
    {
        // Reset sub-group when parent changes — stale child would fail validation anyway.
        $this->inventory_sub_group_id = null;
    }

    /**
     * The type decides which half of the form applies, so switching it clears
     * whatever no longer belongs — otherwise a part switched from Tyre to Common
     * would ghost-save a tyre size it no longer has.
     */
    public function updatedSpareType(): void
    {
        if ($this->spare_type !== SpareMaster::TYPE_TYRE) {
            $this->tyre_dimension = null;
            $this->rim_size = null;
            $this->load_speed_index = null;
            $this->tread_pattern = null;
        }

        if ($this->spare_type !== SpareMaster::TYPE_VEHICLE_SPECIFIC) {
            $this->variant_ids = [];
        }
    }

    /** Only vehicle-specific parts pin to particular variants. */
    public function needsVehicleCompatibility(): bool
    {
        return $this->spare_type === SpareMaster::TYPE_VEHICLE_SPECIFIC;
    }

    public function createSpareBrand(): void
    {
        $this->quickCreate(
            modelClass: SpareBrandMaster::class,
            targetProperty: 'spare_brand_id',
            searchProperty: 'spareBrandSearch',
            permission: 'spare_brand_master.create',
            label: 'Parts brand',
        );
    }

    public function createUom(): void
    {
        $this->quickCreate(
            modelClass: UnitOfMeasureMaster::class,
            targetProperty: 'uom_id',
            searchProperty: 'uomSearch',
            permission: 'unit_of_measure_master.create',
            label: 'Unit of measure',
        );
    }

    /** Goods codes only — SAC is for labour. */
    #[Computed]
    public function hsnCodes()
    {
        return HsnMaster::query()
            ->where('is_active', true)
            ->where('kind', HsnMaster::KIND_HSN)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function brands()
    {
        return $this->pickerOptions(
            query: SpareBrandMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'code'],
            term: $this->spareBrandSearch,
            selected: $this->spare_brand_id,
            columns: ['id', 'name'],
            limit: 30,
        );
    }

    #[Computed]
    public function taxes()
    {
        return TaxMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'gst_percent', 'cess_percent']);
    }

    #[Computed]
    public function parentInventoryGroups()
    {
        return InventoryGroupMaster::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name']);
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
    public function workshopDepartments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function partTypes()
    {
        return PartTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function racks()
    {
        return RackMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function uoms()
    {
        return UnitOfMeasureMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
    }

    #[Computed]
    public function variants()
    {
        return $this->pickerOptions(
            query: VehicleVariantMaster::query()
                ->with(['model.brand'])
                ->where('is_active', true)
                ->orderBy('name'),
            searchColumns: ['name', 'model.name', 'model.brand.name'],
            term: $this->variantSearch,
            selected: $this->variant_ids,
            columns: ['id', 'name', 'model_id'],
            // Compatibility is multi-select, so keep a slightly wider window.
            limit: 30,
        )
            ->map(fn ($v) => [
                'id' => $v->id,
                'label' => trim(($v->model?->brand?->name ?? '').' '.($v->model?->name ?? '').' '.$v->name),
            ]);
    }

    /**
     * Live tax computation for the rate-with-tax hint shown next to the input.
     */
    #[Computed]
    public function rateInclTax(): float
    {
        return round($this->rate_before_tax * (1 + $this->taxMultiplier() / 100), 2);
    }

    /** Combined GST + cess for the picked slab, as a percentage. */
    protected function taxMultiplier(): float
    {
        $tax = $this->tax_id ? TaxMaster::find($this->tax_id) : null;

        return (float) (($tax?->gst_percent ?? 0) + ($tax?->cess_percent ?? 0));
    }

    /**
     * MRP and rate-before-tax are two views of one price, linked by the tax
     * slab: MRP 118 at 18% means a rate of 100. Whichever figure the user typed
     * last is authoritative, and the other is derived from it.
     *
     * Tracking the basis matters because the slab is often picked *after* the
     * price: typing MRP 118 and then choosing 18% must yield a rate of 100, not
     * quietly rewrite the 118 the user just entered.
     *
     * Assigning a property server-side does not re-fire these hooks, so the two
     * fields cannot bounce off each other.
     */
    public function updatedMrp(): void
    {
        $this->priceBasis = 'mrp';
        $this->syncPriceFromBasis();
    }

    public function updatedRateBeforeTax(): void
    {
        $this->priceBasis = 'rate';
        $this->syncPriceFromBasis();
    }

    /** Changing the slab re-derives whichever figure the user did not type. */
    public function updatedTaxId(): void
    {
        $this->syncPriceFromBasis();
    }

    protected function syncPriceFromBasis(): void
    {
        $multiplier = 1 + $this->taxMultiplier() / 100;

        if ($this->priceBasis === 'mrp') {
            if ($this->mrp !== null && $this->mrp > 0) {
                $this->rate_before_tax = round($this->mrp / $multiplier, 2);
            }

            return;
        }

        $this->mrp = $this->rate_before_tax > 0
            ? round($this->rate_before_tax * $multiplier, 2)
            : null;
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'spare_master.update' : 'spare_master.create');

        $data = $this->validate();
        $variants = $data['variant_ids'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['variant_ids'], $data['attachments'], $data['attachmentFiles']);

        $skip = ['rate_before_tax', 'mrp', 'min_qty', 'max_qty', 'is_active', 'spare_type', 'inventory_type', 'spare_brand_id', 'tax_id', 'inventory_group_id', 'inventory_sub_group_id', 'workshop_department_id', 'uom_id', 'part_type_id', 'rack_id'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        $isCreate = $this->editingId === null;

        $spare = DB::transaction(function () use ($data, $variants, $attachments) {
            if ($this->editingId) {
                $s = SpareMaster::findOrFail($this->editingId);
                $s->update($data);
            } else {
                $s = SpareMaster::create($data);
                $this->editingId = $s->id;
            }

            $s->vehicleVariants()->sync(array_map('intval', $variants));
            $this->syncAttachments($s, $attachments);

            return $s;
        });

        $this->attachmentFiles = [];

        Flux::toast(
            text: 'Spare #'.$spare->id.($isCreate ? ' created.' : ' updated.'),
            variant: 'success',
        );

        return redirect()->route('spare-master.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(SpareMaster $spare, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('spares/'.$spare->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $spare->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $spare->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('spare-master::edit');
    }
}
