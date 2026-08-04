<?php

namespace App\Modules\GoodsReturn\Livewire;

use App\Concerns\MovesStock;
use App\Concerns\SearchesPickerOptions;
use App\Modules\ChallanEntry\Models\Challan;
use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use App\Modules\CreditNoteReasonMaster\Models\CreditNoteReasonMaster;
use App\Modules\GoodsReturn\Models\GoodsReturn;
use App\Modules\Inventory\Models\StockEntry;
use App\Modules\Inventory\Services\StockIssuer;
use App\Modules\PurchaseEntry\Models\PurchaseEntry;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\TransportModeMaster\Models\TransportModeMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Goods Return')]
class Edit extends Component
{
    use MovesStock;
    use SearchesPickerOptions;
    use WithFileUploads;

    /** Search term for the server-backed vendors picker. */
    public string $vendorSearch = '';

    public ?int $editingId = null;

    public ?string $return_no = null;

    public string $document_type = 'credit_note';

    public string $credit_note_type = 'tax_credit';

    public ?int $credit_note_reason_id = null;

    public ?int $vendor_id = null;

    public ?int $transport_mode_id = null;

    public ?int $transport_company_id = null;

    public ?int $purchase_entry_id = null;

    public ?int $challan_id = null;

    public ?string $grn_reference = null;

    public ?string $warranty_type = null;

    public ?string $warranty_period = null;

    public ?string $returned_at = null;

    public ?string $notes = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    /** @var list<array<string, mixed>> */
    public array $items = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachmentFiles = [];

    /** @var list<int> */
    public array $removedAttachmentIds = [];

    public function mount(?GoodsReturn $goodsReturn = null): void
    {
        if ($goodsReturn && $goodsReturn->exists) {
            $this->load($goodsReturn);
        }
    }

    protected function load(GoodsReturn $g): void
    {
        $g->load('items');

        $this->editingId = $g->id;
        foreach ([
            'return_no', 'document_type', 'credit_note_type', 'credit_note_reason_id', 'vendor_id', 'transport_mode_id',
            'transport_company_id', 'purchase_entry_id', 'challan_id', 'grn_reference', 'warranty_type', 'warranty_period', 'notes',
        ] as $k) {
            $this->{$k} = $g->{$k};
        }
        $this->returned_at = $g->returned_at?->format('Y-m-d');

        $this->items = $g->items->map(fn ($i) => [
            'id' => $i->id,
            'spare_id' => $i->spare_id,
            'uom_id' => $i->uom_id,
            'tax_id' => $i->tax_id,
            'description' => $i->description,
            'hsn_code' => $i->hsn_code,
            'qty' => (float) $i->qty,
            'unit_rate' => (float) $i->unit_rate,
            'tax_percent' => (float) $i->tax_percent,
            'material_condition' => $i->material_condition,
            'sequence_no' => (int) $i->sequence_no,
        ])->all();
    }

    public function updated(string $name, $value): void
    {
        if (preg_match('/^items\.(\d+)\.spare_id$/', $name, $m) && $value) {
            $spare = SpareMaster::with('tax')->find($value);
            if ($spare) {
                $i = (int) $m[1];
                $this->items[$i]['description'] = $spare->name;
                $this->items[$i]['hsn_code'] = $spare->hsn_code;
                $this->items[$i]['uom_id'] = $spare->uom_id;
                $this->items[$i]['unit_rate'] = (float) $spare->rate_before_tax;
                $this->items[$i]['tax_id'] = $spare->tax_id;
                $this->items[$i]['tax_percent'] = (float) (($spare->tax?->gst_percent ?? 0) + ($spare->tax?->cess_percent ?? 0));
            }
        }
    }

    public function addLine(): void
    {
        $this->items[] = [
            'id' => null, 'spare_id' => null, 'uom_id' => null, 'tax_id' => null, 'description' => '', 'hsn_code' => null,
            'qty' => 1, 'unit_rate' => 0, 'tax_percent' => 0, 'material_condition' => 'new', 'sequence_no' => count($this->items) + 1,
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function removeAttachment(int $id): void
    {
        if (! in_array($id, $this->removedAttachmentIds, true)) {
            $this->removedAttachmentIds[] = $id;
        }
    }

    /**
     * @return array{parts: float, tax: float, grand: float}
     */
    #[Computed]
    public function totals(): array
    {
        $parts = 0.0;
        $tax = 0.0;
        foreach ($this->items as $row) {
            $base = (float) ($row['qty'] ?? 0) * (float) ($row['unit_rate'] ?? 0);
            $parts += $base;
            $tax += $base * (float) ($row['tax_percent'] ?? 0) / 100;
        }

        return ['parts' => round($parts, 2), 'tax' => round($tax, 2), 'grand' => round($parts + $tax, 2)];
    }

    protected function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'document_type' => ['required', Rule::in(array_keys(GoodsReturn::documentTypes()))],
            'credit_note_type' => ['required', Rule::in(array_keys(GoodsReturn::creditNoteTypes()))],
            'credit_note_reason_id' => ['nullable', 'integer', 'exists:credit_note_reasons,id'],
            'transport_mode_id' => ['nullable', 'integer', 'exists:transport_modes,id'],
            'transport_company_id' => ['nullable', 'integer', 'exists:courier_companies,id'],
            'purchase_entry_id' => ['nullable', 'integer', 'exists:purchase_entries,id'],
            'challan_id' => ['nullable', 'integer', 'exists:challans,id'],
            'grn_reference' => ['nullable', 'string', 'max:60'],
            'warranty_type' => ['nullable', Rule::in(array_keys(GoodsReturn::warrantyTypes()))],
            'warranty_period' => ['nullable', Rule::in(array_keys(GoodsReturn::warrantyPeriods()))],
            'returned_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['numeric', 'min:0.01'],
            'items.*.unit_rate' => ['numeric', 'min:0'],
            'items.*.tax_percent' => ['numeric', 'min:0', 'max:100'],
            'items.*.material_condition' => ['required', Rule::in(array_keys(GoodsReturn::materialConditions()))],

            'attachmentFiles.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
        ];
    }

    #[Computed]
    public function vendors()
    {
        return $this->pickerOptions(
            query: VendorMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'vendor_code'],
            term: $this->vendorSearch,
            selected: $this->vendor_id,
            columns: ['id', 'name'],
            limit: 20,
        );
    }

    #[Computed]
    public function creditNoteReasons()
    {
        return CreditNoteReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function transportModes()
    {
        return TransportModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function transportCompanies()
    {
        return CourierCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function purchaseEntries()
    {
        return PurchaseEntry::query()->orderByDesc('created_at')->limit(100)->get(['id', 'purchase_no']);
    }

    #[Computed]
    public function challans()
    {
        return Challan::query()->orderByDesc('created_at')->limit(100)->get(['id', 'challan_no']);
    }

    #[Computed]
    public function spares()
    {
        return SpareMaster::query()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name']);
    }

    #[Computed]
    public function uoms()
    {
        return UnitOfMeasureMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function taxes()
    {
        return TaxMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function existingAttachments()
    {
        if (! $this->editingId) {
            return collect();
        }

        return GoodsReturn::findOrFail($this->editingId)->attachments()
            ->whereNotIn('id', $this->removedAttachmentIds)
            ->get();
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'goods_return.update' : 'goods_return.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        unset($data['items'], $data['attachmentFiles']);

        $totals = $this->totals();
        $data['parts_total'] = $totals['parts'];
        $data['tax_total'] = $totals['tax'];
        $data['grand_total'] = $totals['grand'];

        foreach (['grn_reference', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $return = $this->runStockGuarded(fn () => DB::transaction(function () use ($data, $items, $isCreate) {
            if ($isCreate) {
                $row = GoodsReturn::create($data);
                $this->editingId = $row->id;
                $this->return_no = $row->fresh()->return_no;
            } else {
                $row = GoodsReturn::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncStockReturns($row);
            $this->syncAttachments($row);

            return $row;
        }));

        if ($return === null) {
            return null;
        }

        $this->attachmentFiles = [];
        $this->removedAttachmentIds = [];

        Flux::toast(text: 'Goods return '.$return->fresh()->return_no.($isCreate ? ' created — stock reduced.' : ' updated — stock re-synced.'), variant: 'success');

        if ($isCreate) {
            return redirect()->route('goods-return.edit', $return->id);
        }

        return redirect()->route('goods-return.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncItems(GoodsReturn $return, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $local = $this->items[$i] ?? [];
            $base = (float) ($row['qty'] ?? 0) * (float) ($row['unit_rate'] ?? 0);
            $lineTotal = round($base * (1 + (float) ($row['tax_percent'] ?? 0) / 100), 2);

            $payload = [
                'spare_id' => $local['spare_id'] ?? null,
                'uom_id' => $local['uom_id'] ?? null,
                'tax_id' => $local['tax_id'] ?? null,
                'description' => strtoupper((string) $row['description']),
                'hsn_code' => $local['hsn_code'] ?? null,
                'qty' => (float) ($row['qty'] ?? 1),
                'unit_rate' => (float) ($row['unit_rate'] ?? 0),
                'tax_percent' => (float) ($row['tax_percent'] ?? 0),
                'line_total' => $lineTotal,
                'material_condition' => $row['material_condition'],
                'sequence_no' => $i + 1,
            ];

            if (! empty($local['id'])) {
                $ex = $return->items()->whereKey($local['id'])->first();
                if ($ex) {
                    $ex->update($payload);
                    $keptIds[] = $ex->id;

                    continue;
                }
            }

            $created = $return->items()->create($payload);
            $keptIds[] = $created->id;
            $this->items[$i]['id'] = $created->id;
        }

        $return->items()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Returning goods to a vendor reduces our stock — each spare-linked line
     * posts a negative purchase-return entry. Re-synced on save.
     */
    protected function syncStockReturns(GoodsReturn $return): void
    {
        StockIssuer::reverse($return);

        $movedAt = $return->returned_at ?? now();

        foreach ($return->items()->whereNotNull('spare_id')->get() as $item) {
            // Sending goods back to the vendor draws down the layers they came
            // in on, so the batch being returned is the one that leaves.
            StockIssuer::issue($item->spare_id, (float) $item->qty, StockEntry::TYPE_PURCHASE_RETURN, $return, [
                'moved_at' => $movedAt,
                'notes' => 'Goods return '.$return->return_no,
            ]);
        }
    }

    protected function syncAttachments(GoodsReturn $return): void
    {
        if ($this->removedAttachmentIds) {
            foreach ($return->attachments()->whereIn('id', $this->removedAttachmentIds)->get() as $att) {
                Storage::disk('public')->delete($att->path);
                $att->delete();
            }
        }

        foreach ($this->attachmentFiles as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }
            $return->attachments()->create([
                'path' => $file->store("goods-returns/{$return->id}/attachments", 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        }
    }

    public function render()
    {
        return view('goods-return::edit');
    }
}
