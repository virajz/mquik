<?php

namespace App\Modules\StockCounting\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\Inventory\Services\StockIssuer;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\RackMaster\Models\RackMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\StockCounting\Models\StockCount;
use App\Modules\StockCounting\Models\StockCountAttachment;
use App\Modules\StockCounting\Models\StockCountItem;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
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
#[Title('Stock Counting')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $count_no = null;

    public ?string $count_start_date = null;

    public ?string $count_end_date = null;

    public ?string $counting_method = null;

    public string $verification_status = StockCount::STATUS_PENDING;

    public ?int $storage_location_id = null;

    public ?int $inventory_group_id = null;

    public ?int $team_leader_id = null;

    public ?string $team_name = null;

    public ?string $team_members = null;

    public ?string $notes = null;

    public ?string $remarks = null;

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?StockCount $stockCount = null): void
    {
        if ($stockCount && $stockCount->exists) {
            $this->load($stockCount);

            return;
        }

        $this->items = [$this->blankItem()];
    }

    protected function load(StockCount $c): void
    {
        $c->load(['items', 'attachments']);
        $this->editingId = $c->id;
        foreach ([
            'count_no', 'counting_method', 'verification_status', 'storage_location_id', 'inventory_group_id',
            'team_leader_id', 'team_name', 'team_members', 'notes', 'remarks',
        ] as $k) {
            $this->{$k} = $c->{$k};
        }
        $this->count_start_date = $c->count_start_date?->format('Y-m-d');
        $this->count_end_date = $c->count_end_date?->format('Y-m-d');

        $this->items = $c->items->map(fn (StockCountItem $i) => [
            'id' => $i->id, 'spare_id' => $i->spare_id, 'uom_id' => $i->uom_id, 'hsn_id' => $i->hsn_id,
            'tax_id' => $i->tax_id, 'barcode' => $i->barcode, 'description' => $i->description,
            'quantity' => $i->quantity, 'system_stock' => $i->system_stock, 'physical_stock' => $i->physical_stock,
            'mismatch_reason' => $i->mismatch_reason, 'spares_condition' => $i->spares_condition,
            'purchase_invoice_no' => $i->purchase_invoice_no, 'vendor_name' => $i->vendor_name,
            'remark' => $i->remark, 'spareSearch' => '',
        ])->all();

        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $this->attachments = $c->attachments->map(fn ($x) => [
            'id' => $x->id, 'attachment_type' => $x->attachment_type, 'path' => $x->path,
            'original_name' => $x->original_name, 'notes' => $x->notes,
        ])->all();
    }

    /** @return array<string, mixed> */
    protected function blankItem(): array
    {
        return [
            'id' => null, 'spare_id' => null, 'uom_id' => null, 'hsn_id' => null, 'tax_id' => null,
            'barcode' => null, 'description' => '', 'quantity' => 0, 'system_stock' => 0, 'physical_stock' => 0,
            'mismatch_reason' => null, 'spares_condition' => null, 'purchase_invoice_no' => null,
            'vendor_name' => null, 'remark' => null, 'spareSearch' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'count_start_date' => ['nullable', 'date'],
            'count_end_date' => ['nullable', 'date', 'after_or_equal:count_start_date'],
            'counting_method' => ['nullable', Rule::in(array_keys(StockCount::countingMethods()))],
            'verification_status' => ['required', Rule::in(array_keys(StockCount::verificationStatuses()))],
            'storage_location_id' => ['nullable', 'integer', Rule::exists('racks', 'id')],
            'inventory_group_id' => ['nullable', 'integer', Rule::exists('inventory_groups', 'id')],
            'team_leader_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'team_name' => ['nullable', 'string', 'max:255'],
            'team_members' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:2000'],

            'items' => ['array'],
            'items.*.spare_id' => ['nullable', 'integer', Rule::exists('spares', 'id')],
            'items.*.uom_id' => ['nullable', 'integer', Rule::exists('units_of_measure', 'id')],
            'items.*.hsn_id' => ['nullable', 'integer', Rule::exists('hsn_codes', 'id')],
            'items.*.tax_id' => ['nullable', 'integer', Rule::exists('taxes', 'id')],
            'items.*.barcode' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.system_stock' => ['required', 'numeric'],
            'items.*.physical_stock' => ['required', 'numeric'],
            'items.*.mismatch_reason' => ['nullable', Rule::in(array_keys(StockCountItem::varianceReasons()))],
            'items.*.spares_condition' => ['nullable', Rule::in(array_keys(StockCountItem::sparesConditions()))],
            'items.*.purchase_invoice_no' => ['nullable', 'string', 'max:255'],
            'items.*.vendor_name' => ['nullable', 'string', 'max:255'],
            'items.*.remark' => ['nullable', 'string', 'max:255'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(StockCountAttachment::attachmentTypes()))],
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

    /** Auto-fill a line from the picked spare. */
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
        $this->items[$index]['tax_id'] = $spare->tax_id;
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
    public function storageLocations()
    {
        return RackMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function inventoryGroups()
    {
        return InventoryGroupMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
        $this->authorize($this->editingId ? 'stock_counting.update' : 'stock_counting.create');

        $this->items = array_values(array_filter($this->items, fn ($i) => filled($i['description'] ?? null) || filled($i['spare_id'] ?? null)));

        // Same items must not be repeated multiple times.
        $seen = [];
        foreach ($this->items as $index => $item) {
            $spareId = $item['spare_id'] ?? null;
            if (! $spareId) {
                continue;
            }
            if (in_array($spareId, $seen, true)) {
                $this->addError('items.'.$index.'.spare_id', 'Same items must not be repeated multiple times.');
            }
            $seen[] = $spareId;
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            return null;
        }

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['items'], $data['attachments'], $data['attachmentFiles']);

        foreach (['team_name', 'team_members', 'notes', 'remarks'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $count = DB::transaction(function () use ($data, $items, $attachments, $isCreate) {
            if ($isCreate) {
                $row = StockCount::create($data);
                $this->editingId = $row->id;
                $this->count_no = $row->fresh()->count_no;
            } else {
                $row = StockCount::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncAttachments($row, $attachments);
            $this->syncStockEntries($row);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Stock count '.$count->fresh()->count_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('stock-counting.index');
    }

    /**
     * Post the counted variance to the ledger once the count is completed.
     *
     * The shelf is the authority: a count that finds less than the system holds
     * writes stock down even if that takes the balance negative. Nothing is
     * posted while the count is still pending or in progress, and cancelling a
     * completed count reverses what it posted.
     */
    protected function syncStockEntries(StockCount $count): void
    {
        StockIssuer::reverse($count);

        if ($count->verification_status !== StockCount::STATUS_COMPLETED) {
            return;
        }

        $movedAt = $count->count_end_date ?? now();

        foreach ($count->items()->whereNotNull('spare_id')->get() as $item) {
            StockIssuer::adjust($item->spare_id, (float) $item->diff_qty, $count, [
                'moved_at' => $movedAt,
                'notes' => 'Stock count '.$count->count_no.($item->mismatch_reason ? ' · '.$item->mismatch_reason : ''),
            ]);
        }
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncItems(StockCount $count, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $system = (float) ($row['system_stock'] ?? 0);
            $physical = (float) ($row['physical_stock'] ?? 0);

            $keptIds[] = ChildRows::upsert($count->items(), $row['id'] ?? null,
                [
                    'spare_id' => $row['spare_id'] ?: null,
                    'uom_id' => $row['uom_id'] ?: null,
                    'hsn_id' => $row['hsn_id'] ?: null,
                    'tax_id' => $row['tax_id'] ?: null,
                    'barcode' => $row['barcode'] ?: null,
                    'description' => strtoupper(trim((string) $row['description'])),
                    'quantity' => $row['quantity'],
                    'system_stock' => $system,
                    'physical_stock' => $physical,
                    'diff_qty' => $physical - $system,
                    'mismatch_reason' => $row['mismatch_reason'] ?: null,
                    'spares_condition' => $row['spares_condition'] ?: null,
                    'purchase_invoice_no' => $row['purchase_invoice_no'] ?: null,
                    'vendor_name' => $row['vendor_name'] ? strtoupper(trim((string) $row['vendor_name'])) : null,
                    'remark' => $row['remark'] ?: null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $count->items()->whereKeyNot($keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(StockCount $count, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('stock-counts/'.$count->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = ChildRows::upsert($count->attachments(), $row['id'] ?? null,
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $count->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('stock-counting::edit');
    }
}
