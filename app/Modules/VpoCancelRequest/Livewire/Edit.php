<?php

namespace App\Modules\VpoCancelRequest\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrder;
use App\Modules\VpoCancelRequest\Models\VpoCancelRequest;
use App\Modules\VpoCancelRequest\Models\VpoCancelRequestAttachment;
use App\Modules\VpoCancelRequest\Models\VpoCancelRequestItem;
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
#[Title('VPO Cancel Request')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $request_no = null;

    public ?string $cancellation_request_type = 'full';

    public ?string $cancellation_reason = null;

    public ?string $vendor_category = null;

    public ?int $vendor_id = null;

    public ?int $vendor_purchase_order_id = null;

    public ?int $store_incharge_id = null;

    public ?int $priority_id = null;

    public ?int $follow_up_mode_id = null;

    public string $status = VpoCancelRequest::STATUS_REQUESTED;

    public ?string $cancellation_term = null;

    public ?float $cancellation_charge = null;

    public ?string $advance_payment_status = 'no_advance';

    public ?string $hold_reason = null;

    public ?string $rejection_reason = null;

    public ?string $vendor_rating_type = null;

    public ?string $notes = null;

    public string $vendorSearch = '';

    public string $poSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?VpoCancelRequest $vpoCancelRequest = null): void
    {
        if ($vpoCancelRequest && $vpoCancelRequest->exists) {
            $this->load($vpoCancelRequest);

            return;
        }

        $this->items = [$this->blankItem()];
    }

    protected function load(VpoCancelRequest $r): void
    {
        $r->load(['items', 'attachments']);
        $this->editingId = $r->id;
        foreach ([
            'request_no', 'cancellation_request_type', 'cancellation_reason', 'vendor_category', 'vendor_id',
            'vendor_purchase_order_id', 'store_incharge_id', 'priority_id', 'follow_up_mode_id', 'status',
            'cancellation_term', 'advance_payment_status', 'hold_reason', 'rejection_reason', 'vendor_rating_type', 'notes',
        ] as $k) {
            $this->{$k} = $r->{$k};
        }
        $this->cancellation_charge = $r->cancellation_charge === null ? null : (float) $r->cancellation_charge;

        $this->items = $r->items->map(fn (VpoCancelRequestItem $i) => [
            'id' => $i->id,
            'vendor_purchase_order_id' => $i->vendor_purchase_order_id,
            'job_card_id' => $i->job_card_id,
            'workshop_department_id' => $i->workshop_department_id,
            'advisor_id' => $i->advisor_id,
            'spare_id' => $i->spare_id,
            'spare_brand_id' => $i->spare_brand_id,
            'uom_id' => $i->uom_id,
            'hsn_id' => $i->hsn_id,
            'tax_id' => $i->tax_id,
            'vehicle_variant_id' => $i->vehicle_variant_id,
            'description' => $i->description,
            'quantity' => $i->quantity,
            'quantity_to_cancel' => $i->quantity_to_cancel,
            'rate' => $i->rate,
            'notes' => $i->notes,
            'spareSearch' => '',
            'poSearch' => '',
            'jobCardSearch' => '',
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
            'id' => null, 'vendor_purchase_order_id' => null, 'job_card_id' => null, 'workshop_department_id' => null,
            'advisor_id' => null, 'spare_id' => null, 'spare_brand_id' => null, 'uom_id' => null, 'hsn_id' => null,
            'tax_id' => null, 'vehicle_variant_id' => null, 'description' => '', 'quantity' => 1,
            'quantity_to_cancel' => null, 'rate' => null, 'notes' => null,
            'spareSearch' => '', 'poSearch' => '', 'jobCardSearch' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'cancellation_request_type' => ['required', Rule::in(array_keys(VpoCancelRequest::requestTypes()))],
            'cancellation_reason' => ['nullable', Rule::in(array_keys(VpoCancelRequest::cancellationReasons()))],
            'vendor_category' => ['nullable', Rule::in(array_keys(VpoCancelRequest::vendorCategories()))],
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'vendor_purchase_order_id' => ['nullable', 'integer', Rule::exists('vendor_purchase_orders', 'id')],
            'store_incharge_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'priority_id' => ['nullable', 'integer', Rule::exists('priorities', 'id')],
            'follow_up_mode_id' => ['nullable', 'integer', Rule::exists('follow_up_modes', 'id')],
            'status' => ['required', Rule::in(array_keys(VpoCancelRequest::statuses()))],
            'cancellation_term' => ['nullable', Rule::in(array_keys(VpoCancelRequest::cancellationTerms()))],
            'cancellation_charge' => ['nullable', 'numeric', 'min:0', Rule::requiredIf(fn () => in_array($this->cancellation_term, ['fixed_charge', 'percentage_charge'], true))],
            'advance_payment_status' => ['nullable', Rule::in(array_keys(VpoCancelRequest::advancePaymentStatuses()))],
            'hold_reason' => ['nullable', Rule::in(array_keys(VpoCancelRequest::holdReasons()))],
            'rejection_reason' => ['nullable', Rule::in(array_keys(VpoCancelRequest::rejectionReasons())), Rule::requiredIf(fn () => $this->status === VpoCancelRequest::STATUS_REJECTED)],
            'vendor_rating_type' => ['nullable', Rule::in(array_keys(VpoCancelRequest::vendorRatingTypes()))],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array', 'min:1'],
            'items.*.vendor_purchase_order_id' => ['nullable', 'integer', Rule::exists('vendor_purchase_orders', 'id')],
            'items.*.job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'items.*.workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'items.*.advisor_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'items.*.spare_id' => ['nullable', 'integer', Rule::exists('spares', 'id')],
            'items.*.spare_brand_id' => ['nullable', 'integer', Rule::exists('spare_brands', 'id')],
            'items.*.uom_id' => ['nullable', 'integer', Rule::exists('units_of_measure', 'id')],
            'items.*.hsn_id' => ['nullable', 'integer', Rule::exists('hsn_codes', 'id')],
            'items.*.tax_id' => ['nullable', 'integer', Rule::exists('taxes', 'id')],
            'items.*.vehicle_variant_id' => ['nullable', 'integer', Rule::exists('vehicle_variants', 'id')],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.quantity_to_cancel' => ['nullable', 'numeric', 'min:0'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(VpoCancelRequestAttachment::attachmentTypes()))],
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
        unset($this->items[$index]);
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
    public function departments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
    public function purchaseOrders()
    {
        return $this->pickerOptions(
            query: VendorPurchaseOrder::query()->latest('id'),
            searchColumns: ['po_no'], term: $this->poSearch, selected: $this->vendor_purchase_order_id, columns: ['id', 'po_no'], limit: 30,
        );
    }

    public function poOptions(int $index)
    {
        return $this->pickerOptions(
            query: VendorPurchaseOrder::query()->latest('id'),
            searchColumns: ['po_no'], term: $this->items[$index]['poSearch'] ?? '', selected: $this->items[$index]['vendor_purchase_order_id'] ?? null, columns: ['id', 'po_no'], limit: 30,
        );
    }

    public function jobCardOptions(int $index)
    {
        return $this->pickerOptions(
            query: JobCard::query()->latest('id'),
            searchColumns: ['job_card_no'], term: $this->items[$index]['jobCardSearch'] ?? '', selected: $this->items[$index]['job_card_id'] ?? null, columns: ['id', 'job_card_no'], limit: 30,
        );
    }

    public function spareOptions(int $index)
    {
        return $this->pickerOptions(
            query: SpareMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'spare_code'], term: $this->items[$index]['spareSearch'] ?? '', selected: $this->items[$index]['spare_id'] ?? null, columns: ['id', 'name', 'spare_code'], limit: 30,
        );
    }

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'vpo_cancel_request.update' : 'vpo_cancel_request.create');

        $this->items = array_values(array_filter(
            $this->items,
            fn ($i) => filled($i['description'] ?? null) || filled($i['spare_id'] ?? null),
        ));
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['items'], $data['attachments'], $data['attachmentFiles']);

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }
        if (! in_array($data['cancellation_term'], ['fixed_charge', 'percentage_charge'], true)) {
            $data['cancellation_charge'] = null;
        }

        $isCreate = $this->editingId === null;

        $request = DB::transaction(function () use ($data, $items, $attachments, $isCreate) {
            if ($isCreate) {
                $row = VpoCancelRequest::create($data);
                $this->editingId = $row->id;
                $this->request_no = $row->fresh()->request_no;
            } else {
                $row = VpoCancelRequest::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Cancel request '.$request->fresh()->request_no.($isCreate ? ' raised.' : ' updated.'), variant: 'success');

        return redirect()->route('vpo-cancel-request.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncItems(VpoCancelRequest $request, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = $request->items()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'vendor_purchase_order_id' => $row['vendor_purchase_order_id'] ?: null,
                    'job_card_id' => $row['job_card_id'] ?: null,
                    'workshop_department_id' => $row['workshop_department_id'] ?: null,
                    'advisor_id' => $row['advisor_id'] ?: null,
                    'spare_id' => $row['spare_id'] ?: null,
                    'spare_brand_id' => $row['spare_brand_id'] ?: null,
                    'uom_id' => $row['uom_id'] ?: null,
                    'hsn_id' => $row['hsn_id'] ?: null,
                    'tax_id' => $row['tax_id'] ?: null,
                    'vehicle_variant_id' => $row['vehicle_variant_id'] ?: null,
                    'description' => strtoupper(trim((string) $row['description'])),
                    'quantity' => $row['quantity'],
                    'quantity_to_cancel' => $row['quantity_to_cancel'] !== '' ? $row['quantity_to_cancel'] : null,
                    'rate' => $row['rate'] !== '' ? $row['rate'] : null,
                    'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $request->items()->whereKeyNot($keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(VpoCancelRequest $request, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('vpo-cancel-requests/'.$request->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $request->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $request->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('vpo-cancel-request::edit');
    }
}
