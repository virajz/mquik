<?php

namespace App\Modules\VpoApproval\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\ChargeTypeMaster\Models\ChargeTypeMaster;
use App\Modules\CustomerApprovalTypeMaster\Models\CustomerApprovalTypeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PartTypeMaster\Models\PartTypeMaster;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
use App\Modules\VpoApproval\Models\VpoApproval;
use App\Modules\VpoApproval\Models\VpoApprovalAttachment;
use App\Modules\VpoApproval\Models\VpoApprovalItem;
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
#[Title('VPO Approval')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $approval_no = null;

    public ?string $po_approval_type = 'stock_bulk';

    public ?int $job_card_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $employee_id = null;

    public ?int $vendor_purchase_inquiry_id = null;

    public ?int $vendor_id = null;

    public ?int $part_type_id = null;

    public ?int $priority_id = null;

    public ?int $approval_mode_id = null;

    public ?string $approval_level = null;

    public ?string $payment_term = null;

    public ?string $vendor_category = null;

    public string $status = VpoApproval::STATUS_SENT;

    public ?string $rejection_reason = null;

    public ?string $notes = null;

    public string $vendorSearch = '';

    public string $inquirySearch = '';

    public string $jobCardSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<int, array{id:?int, charge_type_id:?int, amount:mixed}> */
    public array $charges = [];

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?VpoApproval $vpoApproval = null): void
    {
        if ($vpoApproval && $vpoApproval->exists) {
            $this->load($vpoApproval);

            return;
        }

        $this->items = [$this->blankItem()];
    }

    protected function load(VpoApproval $a): void
    {
        $a->load(['items', 'charges', 'attachments']);
        $this->editingId = $a->id;
        foreach ([
            'approval_no', 'po_approval_type', 'job_card_id', 'customer_id', 'customer_vehicle_id',
            'employee_id', 'vendor_purchase_inquiry_id', 'vendor_id', 'part_type_id', 'priority_id',
            'approval_mode_id', 'approval_level', 'payment_term', 'vendor_category', 'status',
            'rejection_reason', 'notes',
        ] as $k) {
            $this->{$k} = $a->{$k};
        }

        $this->items = $a->items->map(fn (VpoApprovalItem $i) => [
            'id' => $i->id,
            'spare_id' => $i->spare_id,
            'spare_brand_id' => $i->spare_brand_id,
            'uom_id' => $i->uom_id,
            'hsn_id' => $i->hsn_id,
            'tax_id' => $i->tax_id,
            'description' => $i->description,
            'quantity' => $i->quantity,
            'part_approved' => (bool) $i->part_approved,
            'qty_approved' => $i->qty_approved,
            'rate_approved' => $i->rate_approved,
            'discount_approved' => $i->discount_approved,
            'tat_approved' => $i->tat_approved,
            'last_purchase_price' => $i->last_purchase_price,
            'last_purchase_vendor' => $i->last_purchase_vendor,
            'spareSearch' => '',
        ])->all();
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $this->charges = $a->charges->map(fn ($c) => ['id' => $c->id, 'charge_type_id' => $c->charge_type_id, 'amount' => $c->amount])->all();
        $this->attachments = $a->attachments->map(fn ($x) => [
            'id' => $x->id, 'attachment_type' => $x->attachment_type, 'path' => $x->path, 'original_name' => $x->original_name, 'notes' => $x->notes,
        ])->all();
    }

    /** @return array<string, mixed> */
    protected function blankItem(): array
    {
        return [
            'id' => null, 'spare_id' => null, 'spare_brand_id' => null, 'uom_id' => null, 'hsn_id' => null, 'tax_id' => null,
            'description' => '', 'quantity' => 1, 'part_approved' => true, 'qty_approved' => null, 'rate_approved' => null,
            'discount_approved' => null, 'tat_approved' => null, 'last_purchase_price' => null, 'last_purchase_vendor' => null, 'spareSearch' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'po_approval_type' => ['required', Rule::in(array_keys(VpoApproval::poApprovalTypes()))],
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id'), Rule::requiredIf(fn () => in_array($this->po_approval_type, ['odd_item', 'high_value'], true))],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'vendor_purchase_inquiry_id' => ['nullable', 'integer', Rule::exists('vendor_purchase_inquiries', 'id')],
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'part_type_id' => ['nullable', 'integer', Rule::exists('part_types', 'id')],
            'priority_id' => ['nullable', 'integer', Rule::exists('priorities', 'id')],
            'approval_mode_id' => ['nullable', 'integer', Rule::exists('customer_approval_types', 'id')],
            'approval_level' => ['nullable', Rule::in(array_keys(VpoApproval::approvalLevels()))],
            'payment_term' => ['nullable', Rule::in(array_keys(VpoApproval::paymentTerms()))],
            'vendor_category' => ['nullable', Rule::in(array_keys(VpoApproval::vendorCategories()))],
            'status' => ['required', Rule::in(array_keys(VpoApproval::statuses()))],
            'rejection_reason' => ['nullable', Rule::in(array_keys(VpoApproval::rejectionReasons())), Rule::requiredIf(fn () => $this->status === VpoApproval::STATUS_REJECTED)],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array', 'min:1'],
            'items.*.spare_id' => ['nullable', 'integer', Rule::exists('spares', 'id')],
            'items.*.spare_brand_id' => ['nullable', 'integer', Rule::exists('spare_brands', 'id')],
            'items.*.uom_id' => ['nullable', 'integer', Rule::exists('units_of_measure', 'id')],
            'items.*.hsn_id' => ['nullable', 'integer', Rule::exists('hsn_codes', 'id')],
            'items.*.tax_id' => ['nullable', 'integer', Rule::exists('taxes', 'id')],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.part_approved' => ['boolean'],
            'items.*.qty_approved' => ['nullable', 'numeric', 'min:0'],
            'items.*.rate_approved' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_approved' => ['nullable', 'numeric', 'min:0'],
            'items.*.tat_approved' => ['nullable', 'string', 'max:20'],
            'items.*.last_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.last_purchase_vendor' => ['nullable', 'string', 'max:255'],

            'charges' => ['array'],
            'charges.*.charge_type_id' => ['nullable', 'integer', Rule::exists('charge_types', 'id')],
            'charges.*.amount' => ['nullable', 'numeric', 'min:0'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(VpoApprovalAttachment::attachmentTypes()))],
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
        $this->attachments[] = ['id' => null, 'attachment_type' => null, 'path' => null, 'original_name' => null, 'notes' => null];
    }

    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index], $this->attachmentFiles[$index]);
        $this->attachments = array_values($this->attachments);
    }

    #[Computed]
    public function partTypes()
    {
        return PartTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function priorities()
    {
        return PriorityMaster::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']);
    }

    #[Computed]
    public function approvalModes()
    {
        return CustomerApprovalTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
    public function chargeTypes()
    {
        return ChargeTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function vendors()
    {
        return $this->pickerOptions(
            query: VendorMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'code'], term: $this->vendorSearch, selected: $this->vendor_id, columns: ['id', 'name'], limit: 30,
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

    public function save()
    {
        $this->authorize($this->editingId ? 'vpo_approval.update' : 'vpo_approval.create');

        $this->items = array_values(array_filter($this->items, fn ($i) => filled($i['description'] ?? null) || filled($i['spare_id'] ?? null)));
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }
        $this->charges = array_values(array_filter($this->charges, fn ($c) => filled($c['charge_type_id'] ?? null)));

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $charges = $data['charges'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['items'], $data['charges'], $data['attachments'], $data['attachmentFiles']);

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }
        $existing = $this->editingId ? VpoApproval::find($this->editingId) : null;
        if ($data['status'] === VpoApproval::STATUS_FULLY_APPROVED && (! $existing || ! $existing->approved_at)) {
            $data['approved_at'] = now();
        }

        $isCreate = $this->editingId === null;

        $approval = DB::transaction(function () use ($data, $items, $charges, $attachments, $isCreate) {
            if ($isCreate) {
                $row = VpoApproval::create($data);
                $this->editingId = $row->id;
                $this->approval_no = $row->fresh()->approval_no;
            } else {
                $row = VpoApproval::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncCharges($row, $charges);
            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'PO approval '.$approval->fresh()->approval_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('vpo-approval.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncItems(VpoApproval $approval, array $rows): void
    {
        $keptIds = [];
        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = ChildRows::upsert($approval->items(), $row['id'] ?? null, [
                'spare_id' => $row['spare_id'] ?: null,
                'spare_brand_id' => $row['spare_brand_id'] ?: null,
                'uom_id' => $row['uom_id'] ?: null,
                'hsn_id' => $row['hsn_id'] ?: null,
                'tax_id' => $row['tax_id'] ?: null,
                'description' => strtoupper(trim((string) $row['description'])),
                'quantity' => $row['quantity'],
                'part_approved' => (bool) ($row['part_approved'] ?? true),
                'qty_approved' => ($row['qty_approved'] ?? '') !== '' ? $row['qty_approved'] : null,
                'rate_approved' => ($row['rate_approved'] ?? '') !== '' ? $row['rate_approved'] : null,
                'discount_approved' => ($row['discount_approved'] ?? '') !== '' ? $row['discount_approved'] : null,
                'tat_approved' => $row['tat_approved'] ?: null,
                'last_purchase_price' => ($row['last_purchase_price'] ?? '') !== '' ? $row['last_purchase_price'] : null,
                'last_purchase_vendor' => $row['last_purchase_vendor'] ?: null,
                'sequence_no' => $i + 1,
            ])->id;
        }
        $approval->items()->whereKeyNot($keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncCharges(VpoApproval $approval, array $rows): void
    {
        $keptIds = [];
        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = ChildRows::upsert($approval->charges(), $row['id'] ?? null, [
                'charge_type_id' => $row['charge_type_id'] ?: null,
                'amount' => ($row['amount'] ?? '') !== '' ? $row['amount'] : 0,
                'sequence_no' => $i + 1,
            ])->id;
        }
        $approval->charges()->whereKeyNot($keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(VpoApproval $approval, array $rows): void
    {
        $keptIds = [];
        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';
            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('vpo-approvals/'.$approval->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }
            if ($path === null) {
                continue;
            }
            $keptIds[] = ChildRows::upsert($approval->attachments(), $row['id'] ?? null, [
                'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
            ])->id;
        }
        $approval->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('vpo-approval::edit');
    }
}
