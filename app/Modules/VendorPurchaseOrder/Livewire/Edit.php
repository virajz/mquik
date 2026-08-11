<?php

namespace App\Modules\VendorPurchaseOrder\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\AdvancePayment\Models\AdvancePayment;
use App\Modules\ChargeTypeMaster\Models\ChargeTypeMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\EstimateRevisionReasonMaster\Models\EstimateRevisionReasonMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PartTypeMaster\Models\PartTypeMaster;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiryItem;
use App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrder;
use App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrderAttachment;
use App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrderItem;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use App\Modules\VpoApproval\Models\VpoApproval;
use App\Support\ChildRows;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Vendor Purchase Order')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $po_no = null;

    public ?string $po_type = 'stock_replenishment';

    public ?int $vendor_id = null;

    public ?int $vendor_type_id = null;

    public ?int $job_card_id = null;

    public ?int $employee_id = null;

    public ?int $priority_id = null;

    /** ?int — passed via ?from-vpi=ID for the VPI → VPO carry-forward. */
    #[Url(as: 'from-vpi')]
    public ?int $fromVpi = null;

    public ?int $vendor_purchase_inquiry_id = null;

    /** Display-only: source VPI number when carried forward. */
    public ?string $sourceVpiNo = null;

    public ?int $vpo_approval_id = null;

    public ?int $advance_payment_id = null;

    public ?int $transport_company_id = null;

    public ?int $cancellation_reason_id = null;

    public ?string $vendor_category = null;

    public ?string $vendor_rating_type = null;

    public ?string $payment_term = null;

    public ?string $delivery_mode = null;

    public ?string $delivery_commitment = null;

    public ?int $delivery_custom_days = null;

    public ?string $expected_delivery_date = null;

    public string $status = VendorPurchaseOrder::STATUS_PENDING;

    public ?string $rejection_reason = null;

    public ?string $cancellation_note = null;

    public string $acknowledgement_status = VendorPurchaseOrder::ACK_PENDING;

    public ?string $courier_company = null;

    public ?string $consignment_no = null;

    public ?string $consignment_date = null;

    public ?string $terms_conditions = null;

    public ?string $notes = null;

    public string $vendorSearch = '';

    public string $transportSearch = '';

    public string $jobCardSearch = '';

    public string $inquirySearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<int, array{id:?int, charge_type_id:?int, amount:mixed}> */
    public array $charges = [];

    /** @var array<int, array{id:?int, attachment_type:?string, kind:string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?VendorPurchaseOrder $vendorPurchaseOrder = null): void
    {
        if ($vendorPurchaseOrder && $vendorPurchaseOrder->exists) {
            $this->load($vendorPurchaseOrder);

            return;
        }

        $this->items = [$this->blankItem()];

        // Carry-forward from a Vendor Purchase Inquiry (?from-vpi=ID): link it,
        // copy the header context, and seed the quoted lines onto the order.
        if ($this->fromVpi) {
            $vpi = VendorPurchaseInquiry::with('items')->find($this->fromVpi);
            if ($vpi) {
                $this->vendor_purchase_inquiry_id = $vpi->id;
                $this->sourceVpiNo = $vpi->vpi_no;
                $this->job_card_id = $vpi->job_card_id;
                $this->vendor_id = $vpi->vendor_id;
                $this->employee_id = $vpi->employee_id;
                $this->priority_id = $vpi->priority_id;
                $this->payment_term = $vpi->payment_term;
                $this->po_type = $vpi->job_card_id ? 'against_job_card' : 'stock_replenishment';

                $lines = $vpi->items
                    ->map(fn (VendorPurchaseInquiryItem $it) => array_merge($this->blankItem(), [
                        'spare_id' => $it->spare_id,
                        'spare_brand_id' => $it->spare_brand_id,
                        'part_type_id' => $it->part_type_id,
                        'uom_id' => $it->uom_id,
                        'hsn_id' => $it->hsn_id,
                        'tax_id' => $it->tax_id,
                        'vehicle_variant_id' => $it->vehicle_variant_id,
                        'description' => $it->description,
                        'quantity' => $it->quantity,
                        // The vendor's quote becomes the ordered rate.
                        'rate' => $it->quoted_rate,
                        'discount_type' => $it->discount_type,
                        'discount_value' => $it->discount_value,
                        'warranty_type' => $it->warranty_type,
                        'warranty_period_value' => $it->warranty_period_value,
                        'warranty_period_unit' => $it->warranty_period_unit ?: 'month',
                        'lead_time_days' => $it->lead_time_days,
                        'notes' => $it->notes,
                    ]))
                    ->values()
                    ->all();

                if (! empty($lines)) {
                    $this->items = $lines;
                }
            }
        }
    }

    protected function load(VendorPurchaseOrder $order): void
    {
        $order->load(['items', 'charges', 'attachments']);

        $this->editingId = $order->id;
        foreach ([
            'po_no', 'po_type', 'vendor_id', 'vendor_type_id', 'job_card_id', 'employee_id', 'priority_id',
            'vendor_purchase_inquiry_id', 'vpo_approval_id', 'advance_payment_id', 'transport_company_id',
            'cancellation_reason_id', 'vendor_category', 'vendor_rating_type', 'payment_term', 'delivery_mode',
            'delivery_commitment', 'delivery_custom_days', 'status', 'rejection_reason', 'cancellation_note',
            'acknowledgement_status', 'courier_company', 'consignment_no', 'terms_conditions', 'notes',
        ] as $k) {
            $this->{$k} = $order->{$k};
        }
        $this->expected_delivery_date = $order->expected_delivery_date?->format('Y-m-d');
        $this->consignment_date = $order->consignment_date?->format('Y-m-d');
        $this->sourceVpiNo = $order->vendor_purchase_inquiry_id
            ? VendorPurchaseInquiry::whereKey($order->vendor_purchase_inquiry_id)->value('vpi_no')
            : null;

        $this->items = $order->items->map(fn (VendorPurchaseOrderItem $i) => [
            'id' => $i->id,
            'spare_id' => $i->spare_id,
            'spare_brand_id' => $i->spare_brand_id,
            'part_type_id' => $i->part_type_id,
            'uom_id' => $i->uom_id,
            'hsn_id' => $i->hsn_id,
            'tax_id' => $i->tax_id,
            'vehicle_variant_id' => $i->vehicle_variant_id,
            'description' => $i->description,
            'quantity' => $i->quantity,
            'rate' => $i->rate,
            'discount_type' => $i->discount_type,
            'discount_value' => $i->discount_value,
            'warranty_type' => $i->warranty_type,
            'warranty_period_value' => $i->warranty_period_value,
            'warranty_period_unit' => $i->warranty_period_unit ?? 'month',
            'lead_time_days' => $i->lead_time_days,
            'closing_stock' => $i->closing_stock,
            'notes' => $i->notes,
            'spareSearch' => '',
        ])->all();

        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $this->charges = $order->charges->map(fn ($c) => [
            'id' => $c->id, 'charge_type_id' => $c->charge_type_id, 'amount' => $c->amount,
        ])->all();

        $this->attachments = $order->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'kind' => $a->kind,
            'path' => $a->path, 'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    /** @return array<string, mixed> */
    protected function blankItem(): array
    {
        return [
            'id' => null, 'spare_id' => null, 'spare_brand_id' => null, 'part_type_id' => null,
            'uom_id' => null, 'hsn_id' => null, 'tax_id' => null, 'vehicle_variant_id' => null,
            'description' => '', 'quantity' => 1, 'rate' => null,
            'discount_type' => null, 'discount_value' => null,
            'warranty_type' => null, 'warranty_period_value' => null, 'warranty_period_unit' => 'month',
            'lead_time_days' => null, 'closing_stock' => null,
            'notes' => null, 'spareSearch' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'po_type' => ['required', Rule::in(array_keys(VendorPurchaseOrder::poTypes()))],
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'vendor_type_id' => ['nullable', 'integer', Rule::exists('vendor_types', 'id')],
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'priority_id' => ['nullable', 'integer', Rule::exists('priorities', 'id')],
            'vendor_purchase_inquiry_id' => ['nullable', 'integer', Rule::exists('vendor_purchase_inquiries', 'id')],
            'vpo_approval_id' => ['nullable', 'integer', Rule::exists('vpo_approvals', 'id')],
            'advance_payment_id' => ['nullable', 'integer', Rule::exists('advance_payments', 'id')],
            'transport_company_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')],
            'cancellation_reason_id' => ['nullable', 'integer', Rule::exists('estimate_revision_reasons', 'id')],
            'vendor_category' => ['nullable', Rule::in(array_keys(VendorPurchaseOrder::vendorCategories()))],
            'vendor_rating_type' => ['nullable', Rule::in(array_keys(VendorPurchaseOrder::vendorRatingTypes()))],
            'payment_term' => ['nullable', Rule::in(array_keys(VendorPurchaseOrder::paymentTerms()))],
            'delivery_mode' => ['nullable', Rule::in(array_keys(VendorPurchaseOrder::deliveryModes()))],
            'delivery_commitment' => ['nullable', Rule::in(array_keys(VendorPurchaseOrder::deliveryCommitments()))],
            'delivery_custom_days' => ['nullable', 'integer', 'min:1', 'max:365', Rule::requiredIf(fn () => $this->delivery_commitment === 'custom')],
            'expected_delivery_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(VendorPurchaseOrder::statuses()))],
            'rejection_reason' => ['nullable', Rule::in(array_keys(VendorPurchaseOrder::rejectionReasons())), Rule::requiredIf(fn () => $this->acknowledgement_status === VendorPurchaseOrder::ACK_REJECTED)],
            'cancellation_note' => ['nullable', 'string', 'max:255'],
            'acknowledgement_status' => ['required', Rule::in(array_keys(VendorPurchaseOrder::acknowledgementStatuses()))],
            'courier_company' => ['nullable', 'string', 'max:120'],
            'consignment_no' => ['nullable', 'string', 'max:80'],
            'consignment_date' => ['nullable', 'date'],
            'terms_conditions' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array', 'min:1'],
            'items.*.spare_id' => ['nullable', 'integer', Rule::exists('spares', 'id')],
            'items.*.spare_brand_id' => ['nullable', 'integer', Rule::exists('spare_brands', 'id')],
            'items.*.part_type_id' => ['nullable', 'integer', Rule::exists('part_types', 'id')],
            'items.*.uom_id' => ['nullable', 'integer', Rule::exists('units_of_measure', 'id')],
            'items.*.hsn_id' => ['nullable', 'integer', Rule::exists('hsn_codes', 'id')],
            'items.*.tax_id' => ['nullable', 'integer', Rule::exists('taxes', 'id')],
            'items.*.vehicle_variant_id' => ['nullable', 'integer', Rule::exists('vehicle_variants', 'id')],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', Rule::in(array_keys(VendorPurchaseOrderItem::discountTypes()))],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.warranty_type' => ['nullable', Rule::in(array_keys(VendorPurchaseOrderItem::warrantyTypes()))],
            'items.*.warranty_period_value' => ['nullable', 'integer', 'min:1', 'max:999'],
            'items.*.warranty_period_unit' => ['nullable', Rule::in(array_keys(VendorPurchaseOrderItem::warrantyUnits()))],
            'items.*.lead_time_days' => ['nullable', 'integer', 'min:0', 'max:999'],
            'items.*.closing_stock' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],

            'charges' => ['array'],
            'charges.*.charge_type_id' => ['nullable', 'integer', Rule::exists('charge_types', 'id')],
            'charges.*.amount' => ['nullable', 'numeric', 'min:0'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(VendorPurchaseOrderAttachment::attachmentTypes()))],
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

    /** Auto-fill a line's brand / part-type / uom / hsn / tax / rate from the picked spare. */
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
        $this->items[$index]['part_type_id'] = $spare->part_type_id;
        $this->items[$index]['uom_id'] = $spare->uom_id;
        $this->items[$index]['hsn_id'] = $spare->hsn_id;
        $this->items[$index]['tax_id'] = $spare->tax_id;
        $this->items[$index]['rate'] = $spare->rate_before_tax;
        if (blank($this->items[$index]['description'])) {
            $this->items[$index]['description'] = $spare->name;
        }
    }

    public function addCharge(): void
    {
        $this->charges[] = ['id' => null, 'charge_type_id' => null, 'amount' => 0];
    }

    public function removeCharge(int $index): void
    {
        unset($this->charges[$index]);
        $this->charges = array_values($this->charges);
    }

    public function addAttachment(): void
    {
        $this->attachments[] = ['id' => null, 'attachment_type' => null, 'kind' => 'image', 'path' => null, 'original_name' => null, 'notes' => null];
    }

    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index], $this->attachmentFiles[$index]);
        $this->attachments = array_values($this->attachments);
    }

    // ---- Pickers -----------------------------------------------------------

    #[Computed]
    public function vendorTypes()
    {
        return VendorTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
    public function cancellationReasons()
    {
        return EstimateRevisionReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function partTypes()
    {
        return PartTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function uoms()
    {
        return UnitOfMeasureMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
    }

    #[Computed]
    public function spareBrands()
    {
        return SpareBrandMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function taxes()
    {
        return TaxMaster::query()->where('is_active', true)->orderBy('gst_percent')->get(['id', 'name', 'gst_percent']);
    }

    #[Computed]
    public function chargeTypes()
    {
        return ChargeTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function approvals()
    {
        return VpoApproval::query()->latest('id')->limit(100)->get(['id', 'approval_no']);
    }

    #[Computed]
    public function advancePayments()
    {
        return AdvancePayment::query()->latest('id')->limit(100)->get(['id', 'payment_no']);
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
    public function jobCards()
    {
        return $this->pickerOptions(
            query: JobCard::query()->latest('id'),
            searchColumns: ['job_card_no'], term: $this->jobCardSearch, selected: $this->job_card_id, columns: ['id', 'job_card_no'], limit: 30,
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
            searchColumns: ['name', 'spare_code'],
            term: $this->items[$index]['spareSearch'] ?? '',
            selected: $this->items[$index]['spare_id'] ?? null,
            columns: ['id', 'name', 'spare_code'],
            limit: 30,
        );
    }

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'vendor_purchase_order.update' : 'vendor_purchase_order.create');

        $this->items = array_values(array_filter(
            $this->items,
            fn ($i) => filled($i['description'] ?? null) || filled($i['spare_id'] ?? null),
        ));
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $this->charges = array_values(array_filter(
            $this->charges,
            fn ($c) => filled($c['charge_type_id'] ?? null),
        ));

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $charges = $data['charges'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['items'], $data['charges'], $data['attachments'], $data['attachmentFiles']);

        foreach (['terms_conditions', 'notes', 'courier_company', 'consignment_no', 'cancellation_note'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }
        if ($data['delivery_commitment'] !== 'custom') {
            $data['delivery_custom_days'] = null;
        }

        $isCreate = $this->editingId === null;

        $order = DB::transaction(function () use ($data, $items, $charges, $attachments, $isCreate) {
            if ($isCreate) {
                $row = VendorPurchaseOrder::create($data);
                $this->editingId = $row->id;
                $this->po_no = $row->fresh()->po_no;
            } else {
                $row = VendorPurchaseOrder::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncCharges($row, $charges);
            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'PO '.$order->fresh()->po_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('vendor-purchase-order.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncItems(VendorPurchaseOrder $order, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = ChildRows::upsert($order->items(), $row['id'] ?? null,
                [
                    'spare_id' => $row['spare_id'] ?: null,
                    'spare_brand_id' => $row['spare_brand_id'] ?: null,
                    'part_type_id' => $row['part_type_id'] ?: null,
                    'uom_id' => $row['uom_id'] ?: null,
                    'hsn_id' => $row['hsn_id'] ?: null,
                    'tax_id' => $row['tax_id'] ?: null,
                    'vehicle_variant_id' => $row['vehicle_variant_id'] ?: null,
                    'description' => strtoupper(trim((string) $row['description'])),
                    'quantity' => $row['quantity'],
                    'rate' => $row['rate'] !== '' ? $row['rate'] : null,
                    'discount_type' => $row['discount_type'] ?: null,
                    'discount_value' => $row['discount_value'] !== '' ? $row['discount_value'] : null,
                    'warranty_type' => $row['warranty_type'] ?: null,
                    'warranty_period_value' => $row['warranty_period_value'] !== '' ? $row['warranty_period_value'] : null,
                    'warranty_period_unit' => $row['warranty_period_unit'] ?: null,
                    'lead_time_days' => $row['lead_time_days'] !== '' ? $row['lead_time_days'] : null,
                    'closing_stock' => $row['closing_stock'] !== '' ? $row['closing_stock'] : null,
                    'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $order->items()->whereKeyNot($keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncCharges(VendorPurchaseOrder $order, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = ChildRows::upsert($order->charges(), $row['id'] ?? null,
                [
                    'charge_type_id' => $row['charge_type_id'] ?: null,
                    'amount' => $row['amount'] !== '' ? $row['amount'] : 0,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $order->charges()->whereKeyNot($keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(VendorPurchaseOrder $order, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('vendor-purchase-orders/'.$order->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = ChildRows::upsert($order->attachments(), $row['id'] ?? null,
                [
                    'attachment_type' => $row['attachment_type'] ?: null,
                    'kind' => $kind,
                    'path' => $path,
                    'original_name' => $originalName,
                    'size_bytes' => $size,
                    'notes' => $row['notes'] ?: null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $order->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('vendor-purchase-order::edit');
    }
}
