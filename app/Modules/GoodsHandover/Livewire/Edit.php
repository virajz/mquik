<?php

namespace App\Modules\GoodsHandover\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FinalWorkOrder\Models\FinalWorkOrder;
use App\Modules\GoodsHandover\Models\GoodsHandover;
use App\Modules\GoodsHandover\Models\GoodsHandoverAttachment;
use App\Modules\GoodsHandover\Models\GoodsHandoverItem;
use App\Modules\GoodsReceipt\Models\GoodsReceipt;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Goods Handover / Parts Return')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $handover_no = null;

    public ?int $workshop_department_id = null;

    public ?int $handover_by_id = null;

    public ?int $received_by_id = null;

    public ?int $verified_by_id = null;

    public ?int $parts_return_by_id = null;

    public ?int $vendor_id = null;

    public ?int $goods_receipt_id = null;

    public ?int $final_work_order_id = null;

    public ?int $job_card_id = null;

    public ?string $material_return_status = null;

    public ?string $return_reason = null;

    public string $status = GoodsHandover::STATUS_RECEIVED;

    public ?string $notes = null;

    public string $vendorSearch = '';

    public string $grnSearch = '';

    public string $fwoSearch = '';

    public string $jobCardSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** Fresh per-item spare photo uploads keyed by item index. */
    public array $photoFiles = [];

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?GoodsHandover $goodsHandover = null): void
    {
        if ($goodsHandover && $goodsHandover->exists) {
            $this->load($goodsHandover);

            return;
        }

        $this->items = [$this->blankItem()];
    }

    protected function load(GoodsHandover $h): void
    {
        $h->load(['items', 'attachments']);
        $this->editingId = $h->id;
        foreach ([
            'handover_no', 'workshop_department_id', 'handover_by_id', 'received_by_id', 'verified_by_id',
            'parts_return_by_id', 'vendor_id', 'goods_receipt_id', 'final_work_order_id', 'job_card_id',
            'material_return_status', 'return_reason', 'status', 'notes',
        ] as $k) {
            $this->{$k} = $h->{$k};
        }

        $this->items = $h->items->map(fn (GoodsHandoverItem $i) => [
            'id' => $i->id,
            'spare_id' => $i->spare_id,
            'spare_brand_id' => $i->spare_brand_id,
            'uom_id' => $i->uom_id,
            'customer_vehicle_id' => $i->customer_vehicle_id,
            'description' => $i->description,
            'quantity' => $i->quantity,
            'returned_quantity' => $i->returned_quantity,
            'material_condition' => $i->material_condition,
            'physical_verification' => $i->physical_verification,
            'damage_type' => $i->damage_type,
            'photo_path' => $i->photo_path,
            'notes' => $i->notes,
            'spareSearch' => '',
            'vehicleSearch' => '',
        ])->all();

        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $this->attachments = $h->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    /** @return array<string, mixed> */
    protected function blankItem(): array
    {
        return [
            'id' => null, 'spare_id' => null, 'spare_brand_id' => null, 'uom_id' => null, 'customer_vehicle_id' => null,
            'description' => '', 'quantity' => 1, 'returned_quantity' => null, 'material_condition' => 'new',
            'physical_verification' => null, 'damage_type' => null, 'photo_path' => null, 'notes' => null,
            'spareSearch' => '', 'vehicleSearch' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'handover_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'received_by_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'verified_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'parts_return_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'vendor_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')],
            'goods_receipt_id' => ['nullable', 'integer', Rule::exists('goods_receipts', 'id')],
            'final_work_order_id' => ['nullable', 'integer', Rule::exists('final_work_orders', 'id')],
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'material_return_status' => ['nullable', Rule::in(array_keys(GoodsHandover::materialReturnStatuses()))],
            'return_reason' => ['nullable', Rule::in(array_keys(GoodsHandover::returnReasons())), Rule::requiredIf(fn () => filled($this->material_return_status))],
            'status' => ['required', Rule::in(array_keys(GoodsHandover::statuses()))],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array', 'min:1'],
            'items.*.spare_id' => ['nullable', 'integer', Rule::exists('spares', 'id')],
            'items.*.spare_brand_id' => ['nullable', 'integer', Rule::exists('spare_brands', 'id')],
            'items.*.uom_id' => ['nullable', 'integer', Rule::exists('units_of_measure', 'id')],
            'items.*.customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.returned_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.material_condition' => ['nullable', Rule::in(array_keys(GoodsHandoverItem::materialConditions()))],
            'items.*.physical_verification' => ['nullable', Rule::in(array_keys(GoodsHandoverItem::physicalVerifications()))],
            'items.*.damage_type' => ['nullable', Rule::in(array_keys(GoodsHandoverItem::damageTypes()))],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'photoFiles.*' => ['nullable', 'image', 'max:8192'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(GoodsHandoverAttachment::attachmentTypes()))],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ];
    }

    public function addItem(): void
    {
        $this->items[] = $this->blankItem();
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index], $this->photoFiles[$index]);
        $this->items = array_values($this->items);
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }
    }

    /** Auto-fill a line's brand / uom / description from the picked spare. */
    public function updatedItems($value, $key): void
    {
        if (! str_ends_with($key, '.spare_id')) {
            return;
        }

        $index = (int) explode('.', $key)[0];
        $spareId = $this->items[$index]['spare_id'] ?? null;
        if (! $spareId) {
            return;
        }

        $spare = SpareMaster::find($spareId);
        if (! $spare) {
            return;
        }

        $this->items[$index]['spare_brand_id'] = $spare->spare_brand_id;
        $this->items[$index]['uom_id'] = $spare->uom_id;
        if (blank($this->items[$index]['description'])) {
            $this->items[$index]['description'] = $spare->name;
        }
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

    // ---- Pickers -----------------------------------------------------------

    #[Computed]
    public function departments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function spareBrands()
    {
        return SpareBrandMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function uoms()
    {
        return UnitOfMeasureMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
    }

    #[Computed]
    public function vendors()
    {
        return $this->pickerOptions(
            query: VendorMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'vendor_code'], term: $this->vendorSearch, selected: $this->vendor_id, columns: ['id', 'name'], limit: 30,
        );
    }

    #[Computed]
    public function goodsReceipts()
    {
        return $this->pickerOptions(
            query: GoodsReceipt::query()->latest('id'),
            searchColumns: ['grn_no'], term: $this->grnSearch, selected: $this->goods_receipt_id, columns: ['id', 'grn_no'], limit: 30,
        );
    }

    #[Computed]
    public function workOrders()
    {
        return $this->pickerOptions(
            query: FinalWorkOrder::query()->latest('id'),
            searchColumns: ['order_no'], term: $this->fwoSearch, selected: $this->final_work_order_id, columns: ['id', 'order_no'], limit: 30,
        );
    }

    #[Computed]
    public function jobCards()
    {
        return $this->pickerOptions(
            query: JobCard::query()->latest('id'),
            searchColumns: ['job_card_no'], term: $this->jobCardSearch, selected: $this->job_card_id, columns: ['id', 'job_card_no'], limit: 30,
        );
    }

    public function spareOptions(int $index)
    {
        return $this->pickerOptions(
            query: SpareMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'spare_code'], term: $this->items[$index]['spareSearch'] ?? '', selected: $this->items[$index]['spare_id'] ?? null, columns: ['id', 'name', 'spare_code'], limit: 30,
        );
    }

    public function vehicleOptions(int $index)
    {
        return $this->pickerOptions(
            query: CustomerVehicleMaster::query()->orderBy('registration_no'),
            searchColumns: ['registration_no'], term: $this->items[$index]['vehicleSearch'] ?? '', selected: $this->items[$index]['customer_vehicle_id'] ?? null, columns: ['id', 'registration_no'], limit: 30,
        );
    }

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'goods_handover.update' : 'goods_handover.create');

        $this->items = array_values(array_filter($this->items, fn ($i) => filled($i['description'] ?? null) || filled($i['spare_id'] ?? null)));
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['items'], $data['attachments'], $data['attachmentFiles'], $data['photoFiles']);

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }

        $isCreate = $this->editingId === null;

        $handover = DB::transaction(function () use ($data, $items, $attachments, $isCreate) {
            if ($isCreate) {
                $row = GoodsHandover::create($data);
                $this->editingId = $row->id;
                $this->handover_no = $row->fresh()->handover_no;
            } else {
                $row = GoodsHandover::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->photoFiles = [];
        $this->attachmentFiles = [];

        Flux::toast(text: 'Handover '.$handover->fresh()->handover_no.($isCreate ? ' recorded.' : ' updated.'), variant: 'success');

        return redirect()->route('goods-handover.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncItems(GoodsHandover $handover, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $photoPath = $this->items[$i]['photo_path'] ?? null;
            $photo = $this->photoFiles[$i] ?? null;
            if ($photo instanceof TemporaryUploadedFile) {
                $photoPath = $photo->store('goods-handovers/'.$handover->id, 'public');
            }

            $keptIds[] = $handover->items()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'spare_id' => $row['spare_id'] ?: null,
                    'spare_brand_id' => $row['spare_brand_id'] ?: null,
                    'uom_id' => $row['uom_id'] ?: null,
                    'customer_vehicle_id' => $row['customer_vehicle_id'] ?: null,
                    'description' => strtoupper(trim((string) $row['description'])),
                    'quantity' => $row['quantity'],
                    'returned_quantity' => $row['returned_quantity'] !== '' ? $row['returned_quantity'] : null,
                    'material_condition' => $row['material_condition'] ?: null,
                    'physical_verification' => $row['physical_verification'] ?: null,
                    'damage_type' => $row['damage_type'] ?: null,
                    'photo_path' => $photoPath,
                    'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $handover->items()->whereKeyNot($keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(GoodsHandover $handover, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('goods-handovers/'.$handover->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $handover->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $handover->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function updatedJobCardId(): void
    {
        $this->prefillFromJobCard();
    }

    protected function prefillFromJobCard(): void
    {
        if (! $this->job_card_id) {
            return;
        }

        $jobCard = JobCard::find($this->job_card_id);

        if (! $jobCard) {
            return;
        }

        $this->workshop_department_id = $jobCard->workshop_department_id;
    }

    public function render()
    {
        return view('goods-handover::edit');
    }
}
