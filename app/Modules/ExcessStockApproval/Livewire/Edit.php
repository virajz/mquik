<?php

namespace App\Modules\ExcessStockApproval\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\ExcessStockApproval\Models\ExcessStockApproval;
use App\Modules\ExcessStockApproval\Models\ExcessStockApprovalAttachment;
use App\Modules\ExcessStockApproval\Models\ExcessStockApprovalItem;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\GoodsHandover\Models\GoodsHandover;
use App\Modules\GoodsReceipt\Models\GoodsReceipt;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
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
#[Title('Excess Stock Approval')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $request_no = null;

    public ?string $excess_stock_reason = null;

    public ?string $vendor_rejection_reason = null;

    public string $priority = 'normal';

    public string $status = ExcessStockApproval::STATUS_REQUESTED;

    public ?int $goods_handover_id = null;

    public ?int $goods_receipt_id = null;

    public ?string $purchase_invoice_reference = null;

    public ?int $workshop_department_id = null;

    public ?int $service_type_id = null;

    public ?int $follow_up_mode_id = null;

    public ?int $advisor_id = null;

    public ?int $store_incharge_id = null;

    public ?int $store_executive_id = null;

    public ?int $mistake_by_id = null;

    public ?string $notes = null;

    public string $handoverSearch = '';

    public string $grnSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?ExcessStockApproval $excessStockApproval = null): void
    {
        if ($excessStockApproval && $excessStockApproval->exists) {
            $this->load($excessStockApproval);

            return;
        }

        $this->items = [$this->blankItem()];
    }

    protected function load(ExcessStockApproval $e): void
    {
        $e->load(['items', 'attachments']);
        $this->editingId = $e->id;
        foreach ([
            'request_no', 'excess_stock_reason', 'vendor_rejection_reason', 'priority', 'status',
            'goods_handover_id', 'goods_receipt_id', 'purchase_invoice_reference', 'workshop_department_id',
            'service_type_id', 'follow_up_mode_id', 'advisor_id', 'store_incharge_id', 'store_executive_id',
            'mistake_by_id', 'notes',
        ] as $k) {
            $this->{$k} = $e->{$k};
        }

        $this->items = $e->items->map(fn (ExcessStockApprovalItem $i) => [
            'id' => $i->id,
            'spare_id' => $i->spare_id,
            'uom_id' => $i->uom_id,
            'hsn_id' => $i->hsn_id,
            'tax_id' => $i->tax_id,
            'description' => $i->description,
            'quantity' => $i->quantity,
            'rate' => $i->rate,
            'notes' => $i->notes,
            'spareSearch' => '',
        ])->all();

        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $this->attachments = $e->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    /** @return array<string, mixed> */
    protected function blankItem(): array
    {
        return [
            'id' => null, 'spare_id' => null, 'uom_id' => null, 'hsn_id' => null, 'tax_id' => null,
            'description' => '', 'quantity' => 1, 'rate' => null, 'notes' => null, 'spareSearch' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'excess_stock_reason' => ['nullable', Rule::in(array_keys(ExcessStockApproval::excessStockReasons()))],
            'vendor_rejection_reason' => ['nullable', Rule::in(array_keys(ExcessStockApproval::vendorRejectionReasons())), Rule::requiredIf(fn () => $this->excess_stock_reason === 'vendor_return_rejected')],
            'priority' => ['required', Rule::in(array_keys(ExcessStockApproval::priorities()))],
            'status' => ['required', Rule::in(array_keys(ExcessStockApproval::statuses()))],
            'goods_handover_id' => ['nullable', 'integer', Rule::exists('goods_handovers', 'id')],
            'goods_receipt_id' => ['nullable', 'integer', Rule::exists('goods_receipts', 'id')],
            'purchase_invoice_reference' => ['nullable', 'string', 'max:255'],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')],
            'follow_up_mode_id' => ['nullable', 'integer', Rule::exists('follow_up_modes', 'id')],
            'advisor_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'store_incharge_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'store_executive_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'mistake_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array', 'min:1'],
            'items.*.spare_id' => ['nullable', 'integer', Rule::exists('spares', 'id')],
            'items.*.uom_id' => ['nullable', 'integer', Rule::exists('units_of_measure', 'id')],
            'items.*.hsn_id' => ['nullable', 'integer', Rule::exists('hsn_codes', 'id')],
            'items.*.tax_id' => ['nullable', 'integer', Rule::exists('taxes', 'id')],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(ExcessStockApprovalAttachment::attachmentTypes()))],
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

    /** Auto-fill a line's uom / hsn / tax / rate / description from the picked spare. */
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
    public function serviceTypes()
    {
        return ServiceTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function followUpModes()
    {
        return FollowUpModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
    public function handovers()
    {
        return $this->pickerOptions(
            query: GoodsHandover::query()->latest('id'),
            searchColumns: ['handover_no'], term: $this->handoverSearch, selected: $this->goods_handover_id, columns: ['id', 'handover_no'], limit: 30,
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
        $this->authorize($this->editingId ? 'excess_stock_approval.update' : 'excess_stock_approval.create');

        $this->items = array_values(array_filter($this->items, fn ($i) => filled($i['description'] ?? null) || filled($i['spare_id'] ?? null)));
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['items'], $data['attachments'], $data['attachmentFiles']);

        foreach (['purchase_invoice_reference', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $request = DB::transaction(function () use ($data, $items, $attachments, $isCreate) {
            if ($isCreate) {
                $data['requested_at'] = now();
                if ($data['status'] === ExcessStockApproval::STATUS_APPROVED) {
                    $data['approved_at'] = now();
                } elseif ($data['status'] === ExcessStockApproval::STATUS_REJECTED) {
                    $data['rejected_at'] = now();
                }
                $row = ExcessStockApproval::create($data);
                $this->editingId = $row->id;
                $this->request_no = $row->fresh()->request_no;
            } else {
                $row = ExcessStockApproval::findOrFail($this->editingId);
                if ($data['status'] === ExcessStockApproval::STATUS_APPROVED && $row->approved_at === null) {
                    $data['approved_at'] = now();
                }
                if ($data['status'] === ExcessStockApproval::STATUS_REJECTED && $row->rejected_at === null) {
                    $data['rejected_at'] = now();
                }
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Excess stock request '.$request->fresh()->request_no.($isCreate ? ' raised.' : ' updated.'), variant: 'success');

        return redirect()->route('excess-stock-approval.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncItems(ExcessStockApproval $request, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = ChildRows::upsert($request->items(), $row['id'] ?? null,
                [
                    'spare_id' => $row['spare_id'] ?: null,
                    'uom_id' => $row['uom_id'] ?: null,
                    'hsn_id' => $row['hsn_id'] ?: null,
                    'tax_id' => $row['tax_id'] ?: null,
                    'description' => strtoupper(trim((string) $row['description'])),
                    'quantity' => $row['quantity'],
                    'rate' => $row['rate'] !== '' ? $row['rate'] : null,
                    'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $request->items()->whereKeyNot($keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(ExcessStockApproval $request, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('excess-stock-approvals/'.$request->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = ChildRows::upsert($request->attachments(), $row['id'] ?? null,
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
        return view('excess-stock-approval::edit');
    }
}
