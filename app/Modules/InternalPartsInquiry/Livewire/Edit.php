<?php

namespace App\Modules\InternalPartsInquiry\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InternalPartsInquiry\Models\InternalPartsInquiry;
use App\Modules\InternalPartsInquiry\Models\InternalPartsInquiryItem;
use App\Modules\Inventory\Services\StockLedger;
use App\Modules\IpiRejectionReasonMaster\Models\IpiRejectionReasonMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PartTypeMaster\Models\PartTypeMaster;
use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use App\Modules\PriorityMaster\Models\PriorityMaster;
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
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Internal Parts Inquiry')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $ipi_no = null;

    /** ?int — passed via ?from-job-card=ID for the JobCard → IPI handoff. */
    #[Url(as: 'from-job-card')]
    public ?int $fromJobCard = null;

    public ?int $job_card_id = null;

    public ?int $workshop_department_id = null;

    public ?int $requested_by_employee_id = null;

    public ?int $target_employee_id = null;

    public ?int $responded_by_employee_id = null;

    /** Display-only: when the store responded (auto-stamped by save). */
    public ?string $responded_at = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $vendor_id = null;

    public ?int $priority_id = null;

    public ?string $inquiry_type = 'against_job_card';

    public ?string $approval_authority = null;

    public ?string $tat_option = null;

    public ?int $tat_custom_days = null;

    public ?string $requested_at = null;

    public ?string $needed_by = null;

    public string $status = InternalPartsInquiry::STATUS_PENDING;

    public ?int $rejection_reason_id = null;

    public ?string $notes = null;

    /** Search terms for the server-side pickers. */
    public string $vendorSearch = '';

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    public string $jobCardSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<int, array{id:?int, photo_type_id:?int, kind:string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    /** Freshly uploaded files keyed by attachment row index. */
    public array $attachmentFiles = [];

    public function mount(?InternalPartsInquiry $internalPartsInquiry = null): void
    {
        if ($internalPartsInquiry && $internalPartsInquiry->exists) {
            $this->load($internalPartsInquiry);

            return;
        }

        $this->requested_at = now()->format('Y-m-d');
        $this->items = [$this->blankItem()];

        // Prefill when raised straight from a job card (?from-job-card=ID).
        if ($this->fromJobCard) {
            $jobCard = JobCard::find($this->fromJobCard);
            if ($jobCard) {
                $this->job_card_id = $jobCard->id;
                $this->customer_id = $jobCard->customer_id;
                $this->customer_vehicle_id = $jobCard->customer_vehicle_id;
                $this->workshop_department_id = $jobCard->workshop_department_id;
                $this->inquiry_type = 'against_job_card';
            }
        }
    }

    protected function load(InternalPartsInquiry $inquiry): void
    {
        $inquiry->load(['items', 'attachments']);

        $this->editingId = $inquiry->id;
        foreach ([
            'ipi_no', 'job_card_id', 'workshop_department_id', 'requested_by_employee_id',
            'target_employee_id', 'responded_by_employee_id', 'customer_id', 'customer_vehicle_id', 'vendor_id', 'priority_id',
            'inquiry_type', 'approval_authority', 'tat_option', 'tat_custom_days', 'status',
            'rejection_reason_id', 'notes',
        ] as $k) {
            $this->{$k} = $inquiry->{$k};
        }
        $this->requested_at = $inquiry->requested_at?->format('Y-m-d');
        $this->needed_by = $inquiry->needed_by?->format('Y-m-d');
        $this->responded_at = $inquiry->responded_at?->format('d M Y, h:i A');

        $this->items = $inquiry->items->map(fn (InternalPartsInquiryItem $i) => [
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
            'rate_before_tax' => $i->rate_before_tax,
            'stock_status' => $i->stock_status,
            'alternative_option' => $i->alternative_option,
            'notes' => $i->notes,
            'spareSearch' => '',
        ])->all();

        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $this->attachments = $inquiry->attachments->map(fn ($a) => [
            'id' => $a->id,
            'photo_type_id' => $a->photo_type_id,
            'kind' => $a->kind,
            'path' => $a->path,
            'original_name' => $a->original_name,
            'notes' => $a->notes,
        ])->all();
    }

    /** @return array<string, mixed> */
    protected function blankItem(): array
    {
        return [
            'id' => null, 'spare_id' => null, 'spare_brand_id' => null, 'part_type_id' => null,
            'uom_id' => null, 'hsn_id' => null, 'tax_id' => null, 'vehicle_variant_id' => null,
            'description' => '', 'quantity' => 1, 'rate_before_tax' => null,
            'stock_status' => null, 'alternative_option' => 'primary', 'notes' => null,
            'spareSearch' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'requested_by_employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'target_employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'responded_by_employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'vendor_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')],
            'priority_id' => ['nullable', 'integer', Rule::exists('priorities', 'id')],
            'inquiry_type' => ['required', Rule::in(array_keys(InternalPartsInquiry::inquiryTypes()))],
            'approval_authority' => ['nullable', Rule::in(array_keys(InternalPartsInquiry::approvalAuthorities()))],
            'tat_option' => ['nullable', Rule::in(array_keys(InternalPartsInquiry::tatOptions()))],
            'tat_custom_days' => ['nullable', 'integer', 'min:1', 'max:365', Rule::requiredIf(fn () => $this->tat_option === 'custom')],
            'requested_at' => ['required', 'date'],
            'needed_by' => ['nullable', 'date', 'after_or_equal:requested_at'],
            'status' => ['required', Rule::in(array_keys(InternalPartsInquiry::statuses()))],
            'rejection_reason_id' => ['nullable', 'integer', Rule::exists('ipi_rejection_reasons', 'id'), Rule::requiredIf(fn () => $this->status === InternalPartsInquiry::STATUS_NOT_AVAILABLE)],
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
            'items.*.rate_before_tax' => ['nullable', 'numeric', 'min:0'],
            'items.*.stock_status' => ['nullable', Rule::in(array_keys(InternalPartsInquiryItem::stockStatuses()))],
            'items.*.alternative_option' => ['nullable', Rule::in(array_keys(InternalPartsInquiryItem::alternativeOptions()))],
            'items.*.notes' => ['nullable', 'string', 'max:255'],

            'attachments' => ['array'],
            'attachments.*.photo_type_id' => ['nullable', 'integer', Rule::exists('photo_types', 'id')],
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

    /**
     * When a spare is picked, default the line's brand / uom / part-type / hsn /
     * tax and rate from the spare master so the advisor doesn't retype them.
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
        $this->items[$index]['rate_before_tax'] = $spare->rate_before_tax;
        if (blank($this->items[$index]['description'])) {
            $this->items[$index]['description'] = $spare->name;
        }

        // Auto-set availability from the live stock ledger (same source as IPO).
        $this->items[$index]['stock_status'] = StockLedger::currentQty($spareId) > 0 ? 'available' : 'not_available';
    }

    /** Live on-hand quantity for a picked spare, for the availability hint in the row. */
    public function onHandQty(int $index): float
    {
        $spareId = $this->items[$index]['spare_id'] ?? null;

        return $spareId ? (float) StockLedger::currentQty($spareId) : 0.0;
    }

    public function addAttachment(): void
    {
        $this->attachments[] = ['id' => null, 'photo_type_id' => null, 'kind' => 'image', 'path' => null, 'original_name' => null, 'notes' => null];
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
    public function rejectionReasons()
    {
        return IpiRejectionReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
    public function photoTypes()
    {
        return PhotoTypeMaster::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'group']);
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
    public function customers()
    {
        return $this->pickerOptions(
            query: CustomerMaster::query()->where('is_active', true)->orderBy('first_name'),
            searchColumns: ['first_name', 'last_name', 'phone'],
            term: $this->customerSearch,
            selected: $this->customer_id,
            columns: ['id', 'first_name', 'last_name'],
            limit: 30,
        );
    }

    #[Computed]
    public function vehicles()
    {
        return $this->pickerOptions(
            query: CustomerVehicleMaster::query()->orderBy('registration_no'),
            searchColumns: ['registration_no'],
            term: $this->vehicleSearch,
            selected: $this->customer_vehicle_id,
            columns: ['id', 'registration_no'],
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

    /**
     * Spare picker for a specific line. Searched server-side against the huge
     * spares table; the picked spare is always retained.
     */
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
        $this->authorize($this->editingId ? 'internal_parts_inquiry.update' : 'internal_parts_inquiry.create');

        // Drop blank line rows so an empty trailing row doesn't fail validation.
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
        if ($data['tat_option'] !== 'custom') {
            $data['tat_custom_days'] = null;
        }

        // Stamp the store response the first time the inquiry reaches a responded state.
        if (in_array($data['status'], InternalPartsInquiry::respondedStatuses(), true)) {
            $existingRespondedAt = $this->editingId
                ? InternalPartsInquiry::whereKey($this->editingId)->value('responded_at')
                : null;
            if (! $existingRespondedAt) {
                $data['responded_at'] = now();
            }
        }

        $isCreate = $this->editingId === null;

        $inquiry = DB::transaction(function () use ($data, $items, $attachments, $isCreate) {
            if ($isCreate) {
                $row = InternalPartsInquiry::create($data);
                $this->editingId = $row->id;
                $this->ipi_no = $row->fresh()->ipi_no;
            } else {
                $row = InternalPartsInquiry::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(
            text: 'Inquiry '.$inquiry->fresh()->ipi_no.($isCreate ? ' created.' : ' updated.'),
            variant: 'success',
        );

        return redirect()->route('internal-parts-inquiry.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncItems(InternalPartsInquiry $inquiry, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = $inquiry->items()->updateOrCreate(
                ['id' => $row['id'] ?? null],
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
                    'rate_before_tax' => $row['rate_before_tax'] !== '' ? $row['rate_before_tax'] : null,
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
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncAttachments(InternalPartsInquiry $inquiry, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('internal-parts-inquiries/'.$inquiry->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $inquiry->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'photo_type_id' => $row['photo_type_id'] ?: null,
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

    public function render()
    {
        return view('internal-parts-inquiry::edit');
    }
}
