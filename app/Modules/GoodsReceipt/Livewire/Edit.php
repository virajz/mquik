<?php

namespace App\Modules\GoodsReceipt\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\GoodsReceipt\Models\GoodsReceipt;
use App\Modules\GoodsReceipt\Models\GoodsReceiptAttachment;
use App\Modules\GoodsReceipt\Models\GoodsReceiptItem;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
use App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrder;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use App\Support\ChildRows;
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
#[Title('Goods Receive & Verification')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $grn_no = null;

    public ?string $goods_receipt_type = 'against_po';

    public ?int $vendor_id = null;

    public ?int $vendor_purchase_order_id = null;

    public ?int $vendor_purchase_inquiry_id = null;

    public ?int $received_by_id = null;

    public ?int $verified_by_id = null;

    public ?int $follow_up_mode_id = null;

    public ?string $delivery_performance = null;

    public ?string $approval_authority = null;

    public ?string $vendor_category = null;

    public ?string $vendor_rating_type = null;

    public string $status = GoodsReceipt::STATUS_PENDING;

    public ?string $notes = null;

    public string $vendorSearch = '';

    public string $poSearch = '';

    public string $inquirySearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** Fresh per-item spare photo uploads keyed by item index. */
    public array $photoFiles = [];

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?GoodsReceipt $goodsReceipt = null): void
    {
        if ($goodsReceipt && $goodsReceipt->exists) {
            $this->load($goodsReceipt);

            return;
        }

        $this->items = [$this->blankItem()];
    }

    protected function load(GoodsReceipt $g): void
    {
        $g->load(['items', 'attachments']);
        $this->editingId = $g->id;
        foreach ([
            'grn_no', 'goods_receipt_type', 'vendor_id', 'vendor_purchase_order_id', 'vendor_purchase_inquiry_id',
            'received_by_id', 'verified_by_id', 'follow_up_mode_id', 'delivery_performance', 'approval_authority',
            'vendor_category', 'vendor_rating_type', 'status', 'notes',
        ] as $k) {
            $this->{$k} = $g->{$k};
        }

        $this->items = $g->items->map(fn (GoodsReceiptItem $i) => [
            'id' => $i->id,
            'job_card_id' => $i->job_card_id,
            'spare_id' => $i->spare_id,
            'spare_brand_id' => $i->spare_brand_id,
            'uom_id' => $i->uom_id,
            'customer_vehicle_id' => $i->customer_vehicle_id,
            'workshop_department_id' => $i->workshop_department_id,
            'floor_received_by_id' => $i->floor_received_by_id,
            'floor_verified_by_id' => $i->floor_verified_by_id,
            'description' => $i->description,
            'quantity' => $i->quantity,
            'rate' => $i->rate,
            'material_condition' => $i->material_condition,
            'physical_verification' => $i->physical_verification,
            'damage_type' => $i->damage_type,
            'storage_allocation' => $i->storage_allocation,
            'part_approved' => (bool) $i->part_approved,
            'quantity_approved' => $i->quantity_approved,
            'rate_approved' => $i->rate_approved,
            'discount_approved' => $i->discount_approved,
            'tat_approved' => $i->tat_approved,
            'last_purchase_price' => $i->last_purchase_price,
            'last_purchase_vendor' => $i->last_purchase_vendor,
            'last_purchase_date' => $i->last_purchase_date?->format('Y-m-d'),
            'photo_path' => $i->photo_path,
            'notes' => $i->notes,
            'spareSearch' => '',
            'jobCardSearch' => '',
            'vehicleSearch' => '',
        ])->all();

        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $this->attachments = $g->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    /** @return array<string, mixed> */
    protected function blankItem(): array
    {
        return [
            'id' => null, 'job_card_id' => null, 'spare_id' => null, 'spare_brand_id' => null, 'uom_id' => null,
            'customer_vehicle_id' => null, 'workshop_department_id' => null, 'floor_received_by_id' => null,
            'floor_verified_by_id' => null, 'description' => '', 'quantity' => 1, 'rate' => null,
            'material_condition' => 'new', 'physical_verification' => 'ok', 'damage_type' => null,
            'storage_allocation' => null, 'part_approved' => false, 'quantity_approved' => null,
            'rate_approved' => null, 'discount_approved' => null, 'tat_approved' => null,
            'last_purchase_price' => null, 'last_purchase_vendor' => null, 'last_purchase_date' => null,
            'photo_path' => null, 'notes' => null, 'spareSearch' => '', 'jobCardSearch' => '', 'vehicleSearch' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'goods_receipt_type' => ['required', Rule::in(array_keys(GoodsReceipt::receiptTypes()))],
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'vendor_purchase_order_id' => ['nullable', 'integer', Rule::exists('vendor_purchase_orders', 'id'), Rule::requiredIf(fn () => $this->goods_receipt_type === 'against_po')],
            'vendor_purchase_inquiry_id' => ['nullable', 'integer', Rule::exists('vendor_purchase_inquiries', 'id')],
            'received_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'verified_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'follow_up_mode_id' => ['nullable', 'integer', Rule::exists('follow_up_modes', 'id')],
            'delivery_performance' => ['nullable', Rule::in(array_keys(GoodsReceipt::deliveryPerformances()))],
            'approval_authority' => ['nullable', Rule::in(array_keys(GoodsReceipt::approvalAuthorities()))],
            'vendor_category' => ['nullable', Rule::in(array_keys(GoodsReceipt::vendorCategories()))],
            'vendor_rating_type' => ['nullable', Rule::in(array_keys(GoodsReceipt::vendorRatingTypes()))],
            'status' => ['required', Rule::in(array_keys(GoodsReceipt::statuses()))],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array', 'min:1'],
            'items.*.job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'items.*.spare_id' => ['nullable', 'integer', Rule::exists('spares', 'id')],
            'items.*.spare_brand_id' => ['nullable', 'integer', Rule::exists('spare_brands', 'id')],
            'items.*.uom_id' => ['nullable', 'integer', Rule::exists('units_of_measure', 'id')],
            'items.*.customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'items.*.workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'items.*.floor_received_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'items.*.floor_verified_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.material_condition' => ['nullable', Rule::in(array_keys(GoodsReceiptItem::materialConditions()))],
            'items.*.physical_verification' => ['nullable', Rule::in(array_keys(GoodsReceiptItem::physicalVerifications()))],
            'items.*.damage_type' => ['nullable', Rule::in(array_keys(GoodsReceiptItem::damageTypes()))],
            'items.*.storage_allocation' => ['nullable', 'string', 'max:20'],
            'items.*.part_approved' => ['boolean'],
            'items.*.quantity_approved' => ['nullable', 'numeric', 'min:0'],
            'items.*.rate_approved' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_approved' => ['nullable', 'numeric', 'min:0'],
            'items.*.tat_approved' => ['nullable', 'string', 'max:40'],
            'items.*.last_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.last_purchase_vendor' => ['nullable', 'string', 'max:255'],
            'items.*.last_purchase_date' => ['nullable', 'date'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'photoFiles.*' => ['nullable', 'image', 'max:8192'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(GoodsReceiptAttachment::attachmentTypes()))],
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

    /** Auto-fill a line's brand / uom / rate / last-purchase context from the picked spare. */
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
        $this->items[$index]['rate'] = $spare->rate_before_tax;
        $this->items[$index]['last_purchase_price'] = $spare->rate_before_tax;
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
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function departments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function followUpModes()
    {
        return FollowUpModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
    public function purchaseOrders()
    {
        return $this->pickerOptions(
            query: VendorPurchaseOrder::query()->latest('id'),
            searchColumns: ['po_no'], term: $this->poSearch, selected: $this->vendor_purchase_order_id, columns: ['id', 'po_no'], limit: 30,
        );
    }

    #[Computed]
    public function inquiries()
    {
        return $this->pickerOptions(
            query: VendorPurchaseInquiry::query()->latest('id'),
            searchColumns: ['vpi_no'], term: $this->inquirySearch, selected: $this->vendor_purchase_inquiry_id, columns: ['id', 'vpi_no'], limit: 30,
        );
    }

    public function spareOptions(int $index)
    {
        return $this->pickerOptions(
            query: SpareMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'spare_code'], term: $this->items[$index]['spareSearch'] ?? '', selected: $this->items[$index]['spare_id'] ?? null, columns: ['id', 'name', 'spare_code'], limit: 30,
        );
    }

    public function jobCardOptions(int $index)
    {
        return $this->pickerOptions(
            query: JobCard::query()->latest('id'),
            searchColumns: ['job_card_no'], term: $this->items[$index]['jobCardSearch'] ?? '', selected: $this->items[$index]['job_card_id'] ?? null, columns: ['id', 'job_card_no'], limit: 30,
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
        $this->authorize($this->editingId ? 'goods_receipt.update' : 'goods_receipt.create');

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

        $receipt = DB::transaction(function () use ($data, $items, $attachments, $isCreate) {
            if ($isCreate) {
                $row = GoodsReceipt::create($data);
                $this->editingId = $row->id;
                $this->grn_no = $row->fresh()->grn_no;
            } else {
                $row = GoodsReceipt::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->photoFiles = [];
        $this->attachmentFiles = [];

        Flux::toast(text: 'GRN '.$receipt->fresh()->grn_no.($isCreate ? ' received.' : ' updated.'), variant: 'success');

        return redirect()->route('goods-receipt.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncItems(GoodsReceipt $receipt, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $photoPath = $this->items[$i]['photo_path'] ?? null;
            $photo = $this->photoFiles[$i] ?? null;
            if ($photo instanceof TemporaryUploadedFile) {
                $photoPath = $photo->store('goods-receipts/'.$receipt->id, 'public');
            }

            $keptIds[] = ChildRows::upsert($receipt->items(), $row['id'] ?? null,
                [
                    'job_card_id' => $row['job_card_id'] ?: null,
                    'spare_id' => $row['spare_id'] ?: null,
                    'spare_brand_id' => $row['spare_brand_id'] ?: null,
                    'uom_id' => $row['uom_id'] ?: null,
                    'customer_vehicle_id' => $row['customer_vehicle_id'] ?: null,
                    'workshop_department_id' => $row['workshop_department_id'] ?: null,
                    'floor_received_by_id' => $row['floor_received_by_id'] ?: null,
                    'floor_verified_by_id' => $row['floor_verified_by_id'] ?: null,
                    'description' => strtoupper(trim((string) $row['description'])),
                    'quantity' => $row['quantity'],
                    'rate' => $row['rate'] !== '' ? $row['rate'] : null,
                    'material_condition' => $row['material_condition'] ?: null,
                    'physical_verification' => $row['physical_verification'] ?: null,
                    'damage_type' => $row['damage_type'] ?: null,
                    'storage_allocation' => $row['storage_allocation'] ? strtoupper($row['storage_allocation']) : null,
                    'part_approved' => (bool) ($row['part_approved'] ?? false),
                    'quantity_approved' => $row['quantity_approved'] !== '' ? $row['quantity_approved'] : null,
                    'rate_approved' => $row['rate_approved'] !== '' ? $row['rate_approved'] : null,
                    'discount_approved' => $row['discount_approved'] !== '' ? $row['discount_approved'] : null,
                    'tat_approved' => $row['tat_approved'] ?: null,
                    'last_purchase_price' => $row['last_purchase_price'] !== '' ? $row['last_purchase_price'] : null,
                    'last_purchase_vendor' => $row['last_purchase_vendor'] ?: null,
                    'last_purchase_date' => $row['last_purchase_date'] ?: null,
                    'photo_path' => $photoPath,
                    'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $receipt->items()->whereKeyNot($keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(GoodsReceipt $receipt, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('goods-receipts/'.$receipt->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = ChildRows::upsert($receipt->attachments(), $row['id'] ?? null,
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $receipt->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('goods-receipt::edit');
    }
}
