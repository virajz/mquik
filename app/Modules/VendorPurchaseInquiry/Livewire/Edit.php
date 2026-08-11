<?php

namespace App\Modules\VendorPurchaseInquiry\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\ChargeTypeMaster\Models\ChargeTypeMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\EstimateRevisionReasonMaster\Models\EstimateRevisionReasonMaster;
use App\Modules\InternalPartsInquiry\Models\InternalPartsInquiry;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PartTypeMaster\Models\PartTypeMaster;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\SalesEstimateApproval\Models\SalesEstimateApproval;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiryAttachment;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiryItem;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiryQuote;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiryVendor;
use App\Modules\VendorPurchaseInquiry\Support\InquiryDispatchPayload;
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
#[Title('Vendor Purchase Inquiry')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $vpi_no = null;

    public ?string $inquiry_type = 'stock_replenishment';

    public ?int $vendor_id = null;

    public ?int $job_card_id = null;

    /** ?int — passed via ?from-ipi=ID for the IPI → VPI carry-forward. */
    #[Url(as: 'from-ipi')]
    public ?int $fromIpi = null;

    public ?int $internal_parts_inquiry_id = null;

    /** The customer's authorisation for this spend, if obtained. */
    public ?int $customer_approval_id = null;

    /** Display-only: source IPI number when carried forward. */
    public ?string $sourceIpiNo = null;

    public ?int $employee_id = null;

    public ?int $priority_id = null;

    public ?int $revision_reason_id = null;

    public ?string $vendor_category = null;

    public ?string $vendor_rating_type = null;

    public ?string $payment_term = null;

    public ?string $comparison_parameter = null;

    public ?string $approval_authority = null;

    public ?string $tat_option = null;

    public ?int $tat_custom_days = null;

    public string $status = VendorPurchaseInquiry::STATUS_PENDING;

    public ?string $terms_conditions = null;

    public ?string $notes = null;

    public string $vendorSearch = '';

    public string $jobCardSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<int, array{id:?int, charge_type_id:?int, amount:mixed}> */
    public array $charges = [];

    /**
     * Vendors this RFQ goes to. An inquiry is normally shopped around several
     * suppliers; each row tracks that vendor's dispatch and reply.
     *
     * @var array<int, array{id:?int, vendor_id:?int, dispatch_channel:?string, response_status:string, quoted_total:mixed, lead_time_days:mixed, warranty_summary:?string, is_selected:bool, sent_at:?string, notes:?string, vendorSearch:string}>
     */
    public array $recipients = [];

    /**
     * Competing offers per line, keyed by the item's index in $items.
     *
     * Two rates on one part is the normal case, not the exception: a vendor
     * quotes genuine and aftermarket, or two vendors quote the same part.
     *
     * @var array<int, array<int, array<string, mixed>>>
     */
    public array $quotes = [];

    /** @var array<int, array{id:?int, attachment_type:?string, kind:string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    /** Freshly uploaded files keyed by attachment row index. */
    public array $attachmentFiles = [];

    public function mount(?VendorPurchaseInquiry $vendorPurchaseInquiry = null): void
    {
        if ($vendorPurchaseInquiry && $vendorPurchaseInquiry->exists) {
            $this->load($vendorPurchaseInquiry);

            return;
        }

        $this->items = [$this->blankItem()];

        // Carry-forward from an Internal Parts Inquiry (?from-ipi=ID): link it,
        // copy the header context, and seed the lines the store couldn't supply.
        if ($this->fromIpi) {
            $ipi = InternalPartsInquiry::with('items')->find($this->fromIpi);
            if ($ipi) {
                $this->internal_parts_inquiry_id = $ipi->id;
                $this->sourceIpiNo = $ipi->ipi_no;
                $this->job_card_id = $ipi->job_card_id;
                $this->vendor_id = $ipi->vendor_id;
                $this->priority_id = $ipi->priority_id;
                $this->inquiry_type = $ipi->job_card_id ? 'against_job_card' : 'stock_replenishment';

                $lines = $ipi->carryForwardItems()
                    ->map(fn ($it) => array_merge($this->blankItem(), [
                        'spare_id' => $it->spare_id,
                        'spare_brand_id' => $it->spare_brand_id,
                        'part_type_id' => $it->part_type_id,
                        'uom_id' => $it->uom_id,
                        'hsn_id' => $it->hsn_id,
                        'tax_id' => $it->tax_id,
                        'vehicle_variant_id' => $it->vehicle_variant_id,
                        'description' => $it->description,
                        'quantity' => $it->quantity,
                        'stock_status' => $it->stock_status === 'not_available' ? 'not_available' : null,
                        'alternative_option' => $it->alternative_option ?: 'primary',
                    ]))
                    ->values()
                    ->all();

                if (! empty($lines)) {
                    $this->items = $lines;
                }
            }
        }
    }

    protected function load(VendorPurchaseInquiry $inquiry): void
    {
        $inquiry->load(['items.quotes', 'charges', 'attachments', 'vendors']);

        $this->editingId = $inquiry->id;
        foreach ([
            'vpi_no', 'inquiry_type', 'vendor_id', 'job_card_id', 'internal_parts_inquiry_id', 'employee_id', 'priority_id',
            'revision_reason_id', 'vendor_category', 'vendor_rating_type', 'payment_term',
            'comparison_parameter', 'approval_authority', 'tat_option', 'tat_custom_days', 'customer_approval_id',
            'status', 'terms_conditions', 'notes',
        ] as $k) {
            $this->{$k} = $inquiry->{$k};
        }
        $this->sourceIpiNo = $inquiry->internal_parts_inquiry_id
            ? InternalPartsInquiry::whereKey($inquiry->internal_parts_inquiry_id)->value('ipi_no')
            : null;

        $this->recipients = $inquiry->vendors->map(fn (VendorPurchaseInquiryVendor $v) => [
            'id' => $v->id,
            'vendor_id' => $v->vendor_id,
            'dispatch_channel' => $v->dispatch_channel,
            'response_status' => $v->response_status,
            'quoted_total' => $v->quoted_total,
            'lead_time_days' => $v->lead_time_days,
            'warranty_summary' => $v->warranty_summary,
            'is_selected' => (bool) $v->is_selected,
            'sent_at' => $v->sent_at?->format('Y-m-d H:i'),
            'notes' => $v->notes,
            'vendorSearch' => '',
        ])->all();

        $this->quotes = $inquiry->items->values()
            ->mapWithKeys(fn (VendorPurchaseInquiryItem $i, int $idx) => [$idx => $i->quotes->map(fn ($q) => [
                'id' => $q->id,
                'vendor_id' => $q->vendor_id,
                'part_type_id' => $q->part_type_id,
                'spare_brand_id' => $q->spare_brand_id,
                'rate' => $q->rate,
                'discount_value' => $q->discount_value,
                'warranty_type' => $q->warranty_type,
                'warranty_period_value' => $q->warranty_period_value,
                'warranty_period_unit' => $q->warranty_period_unit ?: 'month',
                'lead_time_days' => $q->lead_time_days,
                'availability' => $q->availability,
                'is_selected' => (bool) $q->is_selected,
                'notes' => $q->notes,
            ])->values()->all()])
            ->all();

        $this->items = $inquiry->items->map(fn (VendorPurchaseInquiryItem $i) => [
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
            'quoted_rate' => $i->quoted_rate,
            'discount_type' => $i->discount_type,
            'discount_value' => $i->discount_value,
            'warranty_type' => $i->warranty_type,
            'warranty_period_value' => $i->warranty_period_value,
            'warranty_period_unit' => $i->warranty_period_unit ?? 'month',
            'lead_time_days' => $i->lead_time_days,
            'stock_status' => $i->stock_status,
            'alternative_option' => $i->alternative_option,
            'notes' => $i->notes,
            'spareSearch' => '',
        ])->all();

        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $this->charges = $inquiry->charges->map(fn ($c) => [
            'id' => $c->id,
            'charge_type_id' => $c->charge_type_id,
            'amount' => $c->amount,
        ])->all();

        $this->attachments = $inquiry->attachments->map(fn ($a) => [
            'id' => $a->id,
            'attachment_type' => $a->attachment_type,
            'kind' => $a->kind,
            'path' => $a->path,
            'original_name' => $a->original_name,
            'notes' => $a->notes,
        ])->all();
    }

    /** @return array<string, mixed> */
    /** @return array<string, mixed> */
    /** Customer approvals available to attach — scoped to this RFQ's job card. */
    #[Computed]
    public function customerApprovals()
    {
        return SalesEstimateApproval::query()
            ->when($this->job_card_id, fn ($q) => $q->where('job_card_id', $this->job_card_id))
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'approval_no', 'status', 'customer_approved_at']);
    }

    /** True once the linked approval carries a customer sign-off. */
    #[Computed]
    public function isCustomerApproved(): bool
    {
        if (! $this->customer_approval_id) {
            return false;
        }

        return SalesEstimateApproval::whereKey($this->customer_approval_id)
            ->whereNotNull('customer_approved_at')
            ->exists();
    }

    protected function blankRecipient(): array
    {
        return [
            'id' => null, 'vendor_id' => null, 'dispatch_channel' => 'whatsapp',
            'response_status' => VendorPurchaseInquiryVendor::STATUS_AWAITING,
            'quoted_total' => null, 'lead_time_days' => null, 'warranty_summary' => null,
            'is_selected' => false, 'sent_at' => null, 'notes' => null, 'vendorSearch' => '',
        ];
    }

    /** @return array<string, mixed> */
    protected function blankQuote(): array
    {
        return [
            'id' => null, 'vendor_id' => null, 'part_type_id' => null, 'spare_brand_id' => null,
            'rate' => null, 'discount_value' => null,
            'warranty_type' => null, 'warranty_period_value' => null, 'warranty_period_unit' => 'month',
            'lead_time_days' => null, 'availability' => null, 'is_selected' => false, 'notes' => null,
        ];
    }

    public function addQuote(int $itemIndex): void
    {
        $this->quotes[$itemIndex][] = $this->blankQuote();
    }

    public function removeQuote(int $itemIndex, int $quoteIndex): void
    {
        unset($this->quotes[$itemIndex][$quoteIndex]);
        $this->quotes[$itemIndex] = array_values($this->quotes[$itemIndex] ?? []);
    }

    /**
     * Exactly one quote per line wins. Picking one unpicks the rest and copies
     * its price onto the line, which is what the purchase order reads.
     */
    public function selectQuote(int $itemIndex, int $quoteIndex): void
    {
        foreach ($this->quotes[$itemIndex] ?? [] as $q => $row) {
            $this->quotes[$itemIndex][$q]['is_selected'] = ($q === $quoteIndex);
        }

        $picked = $this->quotes[$itemIndex][$quoteIndex] ?? null;
        if (! $picked) {
            return;
        }

        $this->items[$itemIndex]['quoted_rate'] = $picked['rate'];
        $this->items[$itemIndex]['part_type_id'] = $picked['part_type_id'];
        $this->items[$itemIndex]['spare_brand_id'] = $picked['spare_brand_id'];
        $this->items[$itemIndex]['warranty_type'] = $picked['warranty_type'];
        $this->items[$itemIndex]['warranty_period_value'] = $picked['warranty_period_value'];
        $this->items[$itemIndex]['warranty_period_unit'] = $picked['warranty_period_unit'];
        $this->items[$itemIndex]['lead_time_days'] = $picked['lead_time_days'];
    }

    /**
     * Cheapest net rate on a line, so the UI can flag the best offer.
     */
    public function bestQuoteIndex(int $itemIndex): ?int
    {
        $rows = collect($this->quotes[$itemIndex] ?? [])
            ->filter(fn ($q) => filled($q['rate'] ?? null));

        if ($rows->isEmpty()) {
            return null;
        }

        return (int) $rows->sortBy(fn ($q) => (float) $q['rate'] - (float) ($q['discount_value'] ?? 0))
            ->keys()
            ->first();
    }

    public function addRecipient(): void
    {
        $this->recipients[] = $this->blankRecipient();
    }

    public function removeRecipient(int $index): void
    {
        unset($this->recipients[$index]);
        $this->recipients = array_values($this->recipients);
    }

    /** Stamp a vendor as sent — the human does the sending, this records it. */
    public function markSent(int $index): void
    {
        if (isset($this->recipients[$index])) {
            $this->recipients[$index]['sent_at'] = now()->format('Y-m-d H:i');
        }
    }

    /**
     * The message to send a vendor: parts, VIN and any photos on the inquiry.
     *
     * @return array{message: string, vin: ?string, registration_no: ?string, images: list<array{path: string, url: ?string, label: string}>}
     */
    #[Computed]
    public function dispatchPayload(): array
    {
        if (! $this->editingId) {
            return ['message' => '', 'vin' => null, 'registration_no' => null, 'images' => []];
        }

        return InquiryDispatchPayload::for(VendorPurchaseInquiry::findOrFail($this->editingId));
    }

    protected function blankItem(): array
    {
        return [
            'id' => null, 'spare_id' => null, 'spare_brand_id' => null, 'part_type_id' => null,
            'uom_id' => null, 'hsn_id' => null, 'tax_id' => null, 'vehicle_variant_id' => null,
            'description' => '', 'quantity' => 1, 'quoted_rate' => null,
            'discount_type' => null, 'discount_value' => null,
            'warranty_type' => null, 'warranty_period_value' => null, 'warranty_period_unit' => 'month',
            'lead_time_days' => null, 'stock_status' => null, 'alternative_option' => 'primary',
            'notes' => null, 'spareSearch' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'inquiry_type' => ['required', Rule::in(array_keys(VendorPurchaseInquiry::inquiryTypes()))],
            // The header vendor is the chosen supplier. It is only demanded when
            // no recipient rows exist — once the RFQ is shopped around, the choice
            // comes from whichever recipient is ticked (see syncRecipients).
            'vendor_id' => [Rule::requiredIf(fn () => empty($this->recipients)), 'nullable', 'integer', Rule::exists('vendors', 'id')],
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'internal_parts_inquiry_id' => ['nullable', 'integer', Rule::exists('internal_parts_inquiries', 'id')],
            'customer_approval_id' => ['nullable', 'integer', Rule::exists('sales_estimate_approvals', 'id')],
            'quotes' => ['array'],
            'quotes.*' => ['array'],
            'quotes.*.*.id' => ['nullable', 'integer'],
            'quotes.*.*.vendor_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')],
            'quotes.*.*.part_type_id' => ['nullable', 'integer', Rule::exists('part_types', 'id')],
            'quotes.*.*.spare_brand_id' => ['nullable', 'integer', Rule::exists('spare_brands', 'id')],
            'quotes.*.*.rate' => ['nullable', 'numeric', 'min:0'],
            'quotes.*.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'quotes.*.*.warranty_type' => ['nullable', 'string', 'max:20'],
            'quotes.*.*.warranty_period_value' => ['nullable', 'integer', 'min:0'],
            'quotes.*.*.warranty_period_unit' => ['nullable', 'string', 'max:10'],
            'quotes.*.*.lead_time_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'quotes.*.*.availability' => ['nullable', Rule::in(array_keys(VendorPurchaseInquiryQuote::availabilities()))],
            'quotes.*.*.is_selected' => ['boolean'],
            'quotes.*.*.notes' => ['nullable', 'string', 'max:500'],
            'recipients' => ['array'],
            'recipients.*.id' => ['nullable', 'integer'],
            'recipients.*.vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'recipients.*.dispatch_channel' => ['nullable', Rule::in(array_keys(VendorPurchaseInquiryVendor::dispatchChannels()))],
            'recipients.*.response_status' => ['required', Rule::in(array_keys(VendorPurchaseInquiryVendor::responseStatuses()))],
            'recipients.*.quoted_total' => ['nullable', 'numeric', 'min:0'],
            'recipients.*.lead_time_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'recipients.*.warranty_summary' => ['nullable', 'string', 'max:120'],
            'recipients.*.is_selected' => ['boolean'],
            'recipients.*.sent_at' => ['nullable', 'string'],
            'recipients.*.notes' => ['nullable', 'string', 'max:1000'],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'priority_id' => ['nullable', 'integer', Rule::exists('priorities', 'id')],
            'revision_reason_id' => ['nullable', 'integer', Rule::exists('estimate_revision_reasons', 'id')],
            'vendor_category' => ['nullable', Rule::in(array_keys(VendorPurchaseInquiry::vendorCategories()))],
            'vendor_rating_type' => ['nullable', Rule::in(array_keys(VendorPurchaseInquiry::vendorRatingTypes()))],
            'payment_term' => ['nullable', Rule::in(array_keys(VendorPurchaseInquiry::paymentTerms()))],
            'comparison_parameter' => ['nullable', Rule::in(array_keys(VendorPurchaseInquiry::comparisonParameters()))],
            'approval_authority' => ['nullable', Rule::in(array_keys(VendorPurchaseInquiry::approvalAuthorities()))],
            'tat_option' => ['nullable', Rule::in(array_keys(VendorPurchaseInquiry::tatOptions()))],
            'tat_custom_days' => ['nullable', 'integer', 'min:1', 'max:365', Rule::requiredIf(fn () => $this->tat_option === 'custom')],
            'status' => ['required', Rule::in(array_keys(VendorPurchaseInquiry::statuses()))],
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
            'items.*.quoted_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', Rule::in(array_keys(VendorPurchaseInquiryItem::discountTypes()))],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.warranty_type' => ['nullable', Rule::in(array_keys(VendorPurchaseInquiryItem::warrantyTypes()))],
            'items.*.warranty_period_value' => ['nullable', 'integer', 'min:1', 'max:999'],
            'items.*.warranty_period_unit' => ['nullable', Rule::in(array_keys(VendorPurchaseInquiryItem::warrantyUnits()))],
            'items.*.lead_time_days' => ['nullable', 'integer', 'min:0', 'max:999'],
            'items.*.stock_status' => ['nullable', Rule::in(array_keys(VendorPurchaseInquiryItem::stockStatuses()))],
            'items.*.alternative_option' => ['nullable', Rule::in(array_keys(VendorPurchaseInquiryItem::alternativeOptions()))],
            'items.*.notes' => ['nullable', 'string', 'max:255'],

            'charges' => ['array'],
            'charges.*.charge_type_id' => ['nullable', 'integer', Rule::exists('charge_types', 'id')],
            'charges.*.amount' => ['nullable', 'numeric', 'min:0'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(VendorPurchaseInquiryAttachment::attachmentTypes()))],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ];
    }

    public function addItem(): void
    {
        $this->items[] = $this->blankItem();
        $this->quotes[array_key_last($this->items)] = [];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }
    }

    /**
     * Auto-fill a line's brand / part-type / uom / hsn / tax / rate from the
     * picked spare master.
     */
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
        $this->items[$index]['quoted_rate'] = $spare->rate_before_tax;
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
    public function revisionReasons()
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
    public function vendors()
    {
        return $this->pickerOptions(
            query: VendorMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'code'],
            term: $this->vendorSearch,
            selected: $this->vendor_id,
            columns: ['id', 'name'],
            limit: 30,
        );
    }

    #[Computed]
    public function jobCards()
    {
        return $this->pickerOptions(
            query: JobCard::query()->latest('id'),
            searchColumns: ['job_card_no'],
            term: $this->jobCardSearch,
            selected: $this->job_card_id,
            columns: ['id', 'job_card_no'],
            limit: 30,
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
        $this->authorize($this->editingId ? 'vendor_purchase_inquiry.update' : 'vendor_purchase_inquiry.create');

        $this->items = array_values(array_filter(
            $this->items,
            fn ($i) => filled($i['description'] ?? null) || filled($i['spare_id'] ?? null),
        ));
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        // Drop empty charge rows.
        $this->charges = array_values(array_filter(
            $this->charges,
            fn ($c) => filled($c['charge_type_id'] ?? null),
        ));

        // A recipient row without a vendor is just an empty picker.
        $this->recipients = array_values(array_filter(
            $this->recipients,
            fn ($r) => filled($r['vendor_id'] ?? null),
        ));

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $charges = $data['charges'] ?? [];
        $attachments = $data['attachments'] ?? [];
        $recipients = $data['recipients'] ?? [];
        $quotes = $data['quotes'] ?? [];
        unset($data['items'], $data['charges'], $data['attachments'], $data['attachmentFiles'], $data['recipients'], $data['quotes']);

        foreach (['terms_conditions', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }
        if ($data['tat_option'] !== 'custom') {
            $data['tat_custom_days'] = null;
        }

        $isCreate = $this->editingId === null;

        $inquiry = DB::transaction(function () use ($data, $items, $charges, $attachments, $recipients, $quotes, $isCreate) {
            if ($isCreate) {
                $row = VendorPurchaseInquiry::create($data);
                $this->editingId = $row->id;
                $this->vpi_no = $row->fresh()->vpi_no;
            } else {
                $row = VendorPurchaseInquiry::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncRecipients($row, $recipients);
            $this->syncItems($row, $items);
            $this->syncQuotes($row, $quotes);
            $this->syncCharges($row, $charges);
            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(
            text: 'RFQ '.$inquiry->fresh()->vpi_no.($isCreate ? ' created.' : ' updated.'),
            variant: 'success',
        );

        return redirect()->route('vendor-purchase-inquiry.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncItems(VendorPurchaseInquiry $inquiry, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = ChildRows::upsert($inquiry->items(), $row['id'] ?? null,
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
                    'quoted_rate' => $row['quoted_rate'] !== '' ? $row['quoted_rate'] : null,
                    'discount_type' => $row['discount_type'] ?: null,
                    'discount_value' => $row['discount_value'] !== '' ? $row['discount_value'] : null,
                    'warranty_type' => $row['warranty_type'] ?: null,
                    'warranty_period_value' => $row['warranty_period_value'] !== '' ? $row['warranty_period_value'] : null,
                    'warranty_period_unit' => $row['warranty_period_unit'] ?: null,
                    'lead_time_days' => $row['lead_time_days'] !== '' ? $row['lead_time_days'] : null,
                    'stock_status' => $row['stock_status'] ?: null,
                    'alternative_option' => $row['alternative_option'] ?: null,
                    'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $inquiry->items()->whereKeyNot($keptIds)->delete();
    }

    /**
     * Persist the competing offers, keyed to the saved line at the same index.
     *
     * @param  array<int, array<int, array<string, mixed>>>  $rowsByItem
     */
    protected function syncQuotes(VendorPurchaseInquiry $inquiry, array $rowsByItem): void
    {
        $lines = $inquiry->items()->orderBy('sequence_no')->orderBy('id')->get()->values();

        foreach ($lines as $index => $line) {
            $rows = $rowsByItem[$index] ?? [];
            $keptIds = [];

            foreach ($rows as $row) {
                if (blank($row['rate'] ?? null) && blank($row['vendor_id'] ?? null)) {
                    continue;   // an untouched blank row
                }

                $keptIds[] = ChildRows::upsert($line->quotes(), $row['id'] ?? null, [
                    'vendor_id' => $row['vendor_id'] ?: null,
                    'part_type_id' => $row['part_type_id'] ?: null,
                    'spare_brand_id' => $row['spare_brand_id'] ?: null,
                    'rate' => $row['rate'] !== '' ? $row['rate'] : null,
                    'discount_value' => $row['discount_value'] !== '' ? $row['discount_value'] : null,
                    'warranty_type' => $row['warranty_type'] ?: null,
                    'warranty_period_value' => $row['warranty_period_value'] !== '' ? $row['warranty_period_value'] : null,
                    'warranty_period_unit' => $row['warranty_period_unit'] ?: null,
                    'lead_time_days' => $row['lead_time_days'] !== '' ? $row['lead_time_days'] : null,
                    'availability' => $row['availability'] ?: null,
                    'is_selected' => (bool) ($row['is_selected'] ?? false),
                    'notes' => $row['notes'] ?: null,
                ])->id;
            }

            $line->quotes()->whereKeyNot($keptIds)->delete();
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncRecipients(VendorPurchaseInquiry $inquiry, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $row) {
            $keptIds[] = ChildRows::upsert($inquiry->vendors(), $row['id'] ?? null,
                [
                    'vendor_id' => $row['vendor_id'],
                    'dispatch_channel' => $row['dispatch_channel'] ?: null,
                    'response_status' => $row['response_status'],
                    'quoted_total' => $row['quoted_total'] !== '' ? $row['quoted_total'] : null,
                    'lead_time_days' => $row['lead_time_days'] !== '' ? $row['lead_time_days'] : null,
                    'warranty_summary' => $row['warranty_summary'] ?: null,
                    'is_selected' => (bool) ($row['is_selected'] ?? false),
                    'sent_at' => $row['sent_at'] ?: null,
                    'notes' => $row['notes'] ?: null,
                ],
            )->id;
        }

        $inquiry->vendors()->whereKeyNot($keptIds)->delete();

        // The chosen vendor is what the VPI → VPO carry-forward reads.
        $selected = collect($rows)->firstWhere('is_selected', true);
        if ($selected) {
            $inquiry->forceFill(['vendor_id' => $selected['vendor_id']])->saveQuietly();
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncCharges(VendorPurchaseInquiry $inquiry, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = ChildRows::upsert($inquiry->charges(), $row['id'] ?? null,
                [
                    'charge_type_id' => $row['charge_type_id'] ?: null,
                    'amount' => $row['amount'] !== '' ? $row['amount'] : 0,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $inquiry->charges()->whereKeyNot($keptIds)->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncAttachments(VendorPurchaseInquiry $inquiry, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('vendor-purchase-inquiries/'.$inquiry->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = ChildRows::upsert($inquiry->attachments(), $row['id'] ?? null,
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

        $inquiry->attachments()->whereKeyNot($keptIds)->delete();
    }

    /**
     * Whether to show who supplies a part.
     *
     * Procurement picks vendors; an advisor reads the same RFQ for price and
     * part grade only, so the vendor identity is hidden from them rather than
     * being noise they must scroll past.
     */
    /** Read-only for anyone who may view the RFQ but not change it. */
    #[Computed]
    public function canEdit(): bool
    {
        $user = auth()->user();

        return $this->editingId
            ? (bool) $user?->can('vendor_purchase_inquiry.update')
            : (bool) $user?->can('vendor_purchase_inquiry.create');
    }

    #[Computed]
    public function showsVendors(): bool
    {
        return auth()->user()?->can('vendor_purchase_inquiry.manage_vendors') ?? false;
    }

    public function render()
    {
        return view('vendor-purchase-inquiry::edit');
    }
}
