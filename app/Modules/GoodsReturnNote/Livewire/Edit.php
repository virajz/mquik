<?php

namespace App\Modules\GoodsReturnNote\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\GoodsReturnNote\Models\GoodsReturnNote;
use App\Modules\GoodsReturnNote\Models\GoodsReturnNoteAttachment;
use App\Modules\GoodsReturnNote\Models\GoodsReturnNoteItem;
use App\Modules\OutsideLabourBill\Models\OutsideLabourBill;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\RegularSalesInvoice\Models\RegularSalesInvoice;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
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
#[Title('Goods Return Note')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $return_no = null;

    public ?string $return_type = 'warranty';

    public ?string $claim_type = null;

    public ?int $vendor_id = null;

    public ?int $transport_company_id = null;

    public ?int $regular_sales_invoice_id = null;

    public ?int $workshop_department_id = null;

    public ?int $advisor_id = null;

    public ?int $technician_id = null;

    public ?int $store_incharge_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $priority_id = null;

    public ?int $follow_up_mode_id = null;

    public ?string $return_reason = null;

    public ?string $warranty_type = null;

    public ?string $warranty_period = null;

    public ?string $rework_type = null;

    public ?string $tat_option = null;

    public ?int $tat_custom_days = null;

    public string $status = GoodsReturnNote::STATUS_REQUESTED;

    public ?string $counter_proposal = null;

    public ?string $rejection_reason = null;

    public ?string $vendor_rating_type = null;

    public ?float $recovery_amount = null;

    public ?string $reminder_frequency = null;

    public ?int $reminder_custom_days = null;

    public ?string $notes = null;

    public string $vendorSearch = '';

    public string $transportSearch = '';

    public string $invoiceSearch = '';

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** Fresh item before/after photo uploads keyed by item index. */
    public array $beforeFiles = [];

    public array $afterFiles = [];

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?GoodsReturnNote $goodsReturnNote = null): void
    {
        if ($goodsReturnNote && $goodsReturnNote->exists) {
            $this->load($goodsReturnNote);

            return;
        }

        $this->items = [$this->blankItem()];
    }

    protected function load(GoodsReturnNote $r): void
    {
        $r->load(['items', 'attachments']);
        $this->editingId = $r->id;
        foreach ([
            'return_no', 'return_type', 'claim_type', 'vendor_id', 'transport_company_id', 'regular_sales_invoice_id',
            'workshop_department_id', 'advisor_id', 'technician_id', 'store_incharge_id', 'customer_id',
            'customer_vehicle_id', 'priority_id', 'follow_up_mode_id', 'return_reason', 'warranty_type',
            'warranty_period', 'rework_type', 'tat_option', 'tat_custom_days', 'status', 'counter_proposal',
            'rejection_reason', 'vendor_rating_type', 'reminder_frequency', 'reminder_custom_days', 'notes',
        ] as $k) {
            $this->{$k} = $r->{$k};
        }
        $this->recovery_amount = $r->recovery_amount === null ? null : (float) $r->recovery_amount;

        $this->items = $r->items->map(fn (GoodsReturnNoteItem $i) => [
            'id' => $i->id,
            'outside_labour_bill_id' => $i->outside_labour_bill_id,
            'spare_id' => $i->spare_id,
            'spare_brand_id' => $i->spare_brand_id,
            'uom_id' => $i->uom_id,
            'hsn_id' => $i->hsn_id,
            'tax_id' => $i->tax_id,
            'item_type' => $i->item_type,
            'description' => $i->description,
            'quantity' => $i->quantity,
            'rate' => $i->rate,
            'material_condition' => $i->material_condition,
            'before_photo_path' => $i->before_photo_path,
            'after_photo_path' => $i->after_photo_path,
            'notes' => $i->notes,
            'spareSearch' => '',
            'billSearch' => '',
        ])->all();

        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $this->attachments = $r->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    /** @return array<string, mixed> */
    protected function blankItem(): array
    {
        return [
            'id' => null, 'outside_labour_bill_id' => null, 'spare_id' => null, 'spare_brand_id' => null,
            'uom_id' => null, 'hsn_id' => null, 'tax_id' => null, 'item_type' => 'spare', 'description' => '',
            'quantity' => 1, 'rate' => null, 'material_condition' => null,
            'before_photo_path' => null, 'after_photo_path' => null, 'notes' => null,
            'spareSearch' => '', 'billSearch' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'return_type' => ['required', Rule::in(array_keys(GoodsReturnNote::returnTypes()))],
            'claim_type' => ['nullable', Rule::in(array_keys(GoodsReturnNote::claimTypes()))],
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'transport_company_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')],
            'regular_sales_invoice_id' => ['nullable', 'integer', Rule::exists('regular_sales_invoices', 'id')],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'advisor_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'technician_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'store_incharge_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'priority_id' => ['nullable', 'integer', Rule::exists('priorities', 'id')],
            'follow_up_mode_id' => ['nullable', 'integer', Rule::exists('follow_up_modes', 'id')],
            'return_reason' => ['nullable', Rule::in(array_keys(GoodsReturnNote::returnReasons()))],
            'warranty_type' => ['nullable', Rule::in(array_keys(GoodsReturnNote::warrantyTypes()))],
            'warranty_period' => ['nullable', Rule::in(array_keys(GoodsReturnNote::warrantyPeriods()))],
            'rework_type' => ['nullable', Rule::in(array_keys(GoodsReturnNote::reworkTypes()))],
            'tat_option' => ['nullable', Rule::in(array_keys(GoodsReturnNote::tatOptions()))],
            'tat_custom_days' => ['nullable', 'integer', 'min:1', 'max:365', Rule::requiredIf(fn () => $this->tat_option === 'custom')],
            'status' => ['required', Rule::in(array_keys(GoodsReturnNote::statuses()))],
            'counter_proposal' => ['nullable', Rule::in(array_keys(GoodsReturnNote::counterProposals())), Rule::requiredIf(fn () => $this->status === GoodsReturnNote::STATUS_COUNTER_PROPOSAL)],
            'rejection_reason' => ['nullable', Rule::in(array_keys(GoodsReturnNote::rejectionReasons())), Rule::requiredIf(fn () => $this->status === GoodsReturnNote::STATUS_REJECTED)],
            'vendor_rating_type' => ['nullable', Rule::in(array_keys(GoodsReturnNote::vendorRatingTypes()))],
            'recovery_amount' => ['nullable', 'numeric', 'min:0'],
            'reminder_frequency' => ['nullable', Rule::in(array_keys(GoodsReturnNote::reminderFrequencies()))],
            'reminder_custom_days' => ['nullable', 'integer', 'min:1', 'max:90', Rule::requiredIf(fn () => $this->reminder_frequency === 'custom')],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array', 'min:1'],
            'items.*.outside_labour_bill_id' => ['nullable', 'integer', Rule::exists('outside_labour_bills', 'id')],
            'items.*.spare_id' => ['nullable', 'integer', Rule::exists('spares', 'id')],
            'items.*.spare_brand_id' => ['nullable', 'integer', Rule::exists('spare_brands', 'id')],
            'items.*.uom_id' => ['nullable', 'integer', Rule::exists('units_of_measure', 'id')],
            'items.*.hsn_id' => ['nullable', 'integer', Rule::exists('hsn_codes', 'id')],
            'items.*.tax_id' => ['nullable', 'integer', Rule::exists('taxes', 'id')],
            'items.*.item_type' => ['required', Rule::in(array_keys(GoodsReturnNoteItem::itemTypes()))],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.material_condition' => ['nullable', Rule::in(array_keys(GoodsReturnNoteItem::materialConditions()))],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'beforeFiles.*' => ['nullable', 'image', 'max:8192'],
            'afterFiles.*' => ['nullable', 'image', 'max:8192'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(GoodsReturnNoteAttachment::attachmentTypes()))],
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
        unset($this->items[$index], $this->beforeFiles[$index], $this->afterFiles[$index]);
        $this->items = array_values($this->items);
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }
    }

    /** Auto-fill a line's brand / uom / hsn / tax / rate / description from the picked spare. */
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
        $this->items[$index]['hsn_id'] = $spare->hsn_id;
        $this->items[$index]['tax_id'] = $spare->tax_id;
        $this->items[$index]['rate'] = $spare->rate_before_tax;
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
    public function priorities()
    {
        return PriorityMaster::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']);
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
    public function taxes()
    {
        return TaxMaster::query()->where('is_active', true)->orderBy('gst_percent')->get(['id', 'name', 'gst_percent']);
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
    public function transportCompanies()
    {
        return $this->pickerOptions(
            query: VendorMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'vendor_code'], term: $this->transportSearch, selected: $this->transport_company_id, columns: ['id', 'name'], limit: 30,
        );
    }

    #[Computed]
    public function invoices()
    {
        return $this->pickerOptions(
            query: RegularSalesInvoice::query()->latest('id'),
            searchColumns: ['invoice_no'], term: $this->invoiceSearch, selected: $this->regular_sales_invoice_id, columns: ['id', 'invoice_no'], limit: 30,
        );
    }

    #[Computed]
    public function customers()
    {
        return $this->pickerOptions(
            query: CustomerMaster::query()->where('is_active', true)->orderBy('first_name'),
            searchColumns: ['first_name', 'last_name', 'phone'], term: $this->customerSearch, selected: $this->customer_id, columns: ['id', 'first_name', 'last_name'], limit: 30,
        );
    }

    #[Computed]
    public function vehicles()
    {
        return $this->pickerOptions(
            query: CustomerVehicleMaster::query()->orderBy('registration_no'),
            searchColumns: ['registration_no'], term: $this->vehicleSearch, selected: $this->customer_vehicle_id, columns: ['id', 'registration_no'], limit: 30,
        );
    }

    public function spareOptions(int $index)
    {
        return $this->pickerOptions(
            query: SpareMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'spare_code'], term: $this->items[$index]['spareSearch'] ?? '', selected: $this->items[$index]['spare_id'] ?? null, columns: ['id', 'name', 'spare_code'], limit: 30,
        );
    }

    public function billOptions(int $index)
    {
        return $this->pickerOptions(
            query: OutsideLabourBill::query()->latest('id'),
            searchColumns: ['bill_no'], term: $this->items[$index]['billSearch'] ?? '', selected: $this->items[$index]['outside_labour_bill_id'] ?? null, columns: ['id', 'bill_no'], limit: 30,
        );
    }

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'goods_return_note.update' : 'goods_return_note.create');

        $this->items = array_values(array_filter($this->items, fn ($i) => filled($i['description'] ?? null) || filled($i['spare_id'] ?? null)));
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['items'], $data['attachments'], $data['attachmentFiles'], $data['beforeFiles'], $data['afterFiles']);

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }
        if ($data['tat_option'] !== 'custom') {
            $data['tat_custom_days'] = null;
        }
        if ($data['reminder_frequency'] !== 'custom') {
            $data['reminder_custom_days'] = null;
        }

        $isCreate = $this->editingId === null;

        $return = DB::transaction(function () use ($data, $items, $attachments, $isCreate) {
            if ($isCreate) {
                $row = GoodsReturnNote::create($data);
                $this->editingId = $row->id;
                $this->return_no = $row->fresh()->return_no;
            } else {
                $row = GoodsReturnNote::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->beforeFiles = [];
        $this->afterFiles = [];
        $this->attachmentFiles = [];

        Flux::toast(text: 'Return '.$return->fresh()->return_no.($isCreate ? ' raised.' : ' updated.'), variant: 'success');

        return redirect()->route('goods-return-note.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncItems(GoodsReturnNote $return, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $beforePath = $this->items[$i]['before_photo_path'] ?? null;
            $afterPath = $this->items[$i]['after_photo_path'] ?? null;

            $before = $this->beforeFiles[$i] ?? null;
            if ($before instanceof TemporaryUploadedFile) {
                $beforePath = $before->store('goods-return-notes/'.$return->id, 'public');
            }
            $after = $this->afterFiles[$i] ?? null;
            if ($after instanceof TemporaryUploadedFile) {
                $afterPath = $after->store('goods-return-notes/'.$return->id, 'public');
            }

            $keptIds[] = $return->items()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'outside_labour_bill_id' => $row['outside_labour_bill_id'] ?: null,
                    'spare_id' => $row['spare_id'] ?: null,
                    'spare_brand_id' => $row['spare_brand_id'] ?: null,
                    'uom_id' => $row['uom_id'] ?: null,
                    'hsn_id' => $row['hsn_id'] ?: null,
                    'tax_id' => $row['tax_id'] ?: null,
                    'item_type' => $row['item_type'] ?: 'spare',
                    'description' => strtoupper(trim((string) $row['description'])),
                    'quantity' => $row['quantity'],
                    'rate' => $row['rate'] !== '' ? $row['rate'] : null,
                    'material_condition' => $row['material_condition'] ?: null,
                    'before_photo_path' => $beforePath,
                    'after_photo_path' => $afterPath,
                    'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $return->items()->whereKeyNot($keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(GoodsReturnNote $return, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('goods-return-notes/'.$return->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $return->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $return->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('goods-return-note::edit');
    }
}
