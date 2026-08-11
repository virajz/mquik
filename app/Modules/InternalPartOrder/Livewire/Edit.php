<?php

namespace App\Modules\InternalPartOrder\Livewire;

use App\Concerns\MovesStock;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InternalPartOrder\Models\InternalPartOrder;
use App\Modules\InternalPartsInquiry\Models\InternalPartsInquiry;
use App\Modules\Inventory\Models\StockEntry;
use App\Modules\Inventory\Services\StockIssuer;
use App\Modules\Inventory\Services\StockLedger;
use App\Modules\IpoCancellationReasonMaster\Models\IpoCancellationReasonMaster;
use App\Modules\IpoRejectionReasonMaster\Models\IpoRejectionReasonMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\ReturnTypeMaster\Models\ReturnTypeMaster;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Support\Carbon;
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
#[Title('Internal Part Order')]
class Edit extends Component
{
    use MovesStock;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $order_no = null;

    public string $ipo_type = 'job_card_requirement';

    public ?int $priority_id = null;

    public string $status = 'draft';

    public ?int $job_card_id = null;

    public ?int $sales_estimate_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $department_id = null;

    public ?int $service_type_id = null;

    public ?int $requested_by_id = null;

    public ?int $technician_id = null;

    public ?int $store_incharge_id = null;

    public ?string $approval_authority = null;

    public string $approval_status = 'pending';

    public ?int $approved_by_id = null;

    public ?int $rejection_reason_id = null;

    public ?int $cancellation_reason_id = null;

    public ?string $notes = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    /** ?int — passed via ?from-ipi=ID for the IPI → IPO carry-forward. */
    #[Url(as: 'from-ipi')]
    public ?int $fromIpi = null;

    public ?int $internal_parts_inquiry_id = null;

    /** Display-only: source IPI number when carried forward. */
    public ?string $sourceIpiNo = null;

    #[Url(as: 'from-job-card')]
    public ?int $fromJobCard = null;

    /** @var list<array<string, mixed>> */
    public array $items = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $itemBeforeFiles = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $itemAfterFiles = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachmentFiles = [];

    /** @var list<int> */
    public array $removedAttachmentIds = [];

    public function mount(?InternalPartOrder $internalPartOrder = null): void
    {
        if ($internalPartOrder && $internalPartOrder->exists) {
            $this->load($internalPartOrder);

            return;
        }

        if ($this->fromJobCard) {
            $jc = JobCard::find($this->fromJobCard);
            if ($jc) {
                $this->job_card_id = $jc->id;
                $this->customer_id = $jc->customer_id;
                $this->customer_vehicle_id = $jc->customer_vehicle_id;
            }
        }

        // Carry-forward from an Internal Parts Inquiry (?from-ipi=ID): the store
        // confirmed it can supply, so the inquiry becomes an order on the store.
        if ($this->fromIpi) {
            $ipi = InternalPartsInquiry::with('items')->find($this->fromIpi);
            if ($ipi) {
                $this->internal_parts_inquiry_id = $ipi->id;
                $this->sourceIpiNo = $ipi->ipi_no;
                $this->job_card_id = $ipi->job_card_id;
                $this->customer_id = $ipi->customer_id;
                $this->customer_vehicle_id = $ipi->customer_vehicle_id;
                $this->department_id = $ipi->workshop_department_id;
                $this->requested_by_id = $ipi->requested_by_employee_id;
                $this->store_incharge_id = $ipi->target_employee_id;
                $this->ipo_type = $ipi->job_card_id ? 'against_job_card' : 'stock_replenishment';

                // Only what the store said it can supply — the rest goes to a vendor.
                $lines = $ipi->storeOrderItems()->map(fn ($it) => [
                    'id' => null,
                    'spare_id' => $it->spare_id,
                    'uom_id' => $it->uom_id,
                    'return_type_id' => null,
                    'description' => $it->description,
                    'is_alternate' => ($it->alternative_option ?? 'primary') !== 'primary',
                    'qty_requested' => (float) $it->quantity,
                    'qty_issued' => 0,
                    'qty_returned' => 0,
                    'stock_status' => $it->stock_status === 'not_available' ? 'not_available' : 'available',
                    'issue_status' => 'pending',
                    'return_status' => null,
                    'before_photo_path' => null,
                    'after_photo_path' => null,
                    'notes' => $it->notes,
                    'sequence_no' => (int) $it->sequence_no,
                ])->values()->all();

                if (! empty($lines)) {
                    $this->items = $lines;
                }
            }
        }
    }

    protected function load(InternalPartOrder $o): void
    {
        $o->load('items');

        $this->editingId = $o->id;
        foreach ([
            'order_no', 'ipo_type', 'priority_id', 'status', 'job_card_id', 'sales_estimate_id', 'customer_id',
            'customer_vehicle_id', 'department_id', 'service_type_id', 'requested_by_id', 'technician_id',
            'store_incharge_id', 'approval_authority', 'approval_status', 'approved_by_id', 'rejection_reason_id',
            'cancellation_reason_id', 'notes', 'internal_parts_inquiry_id',
        ] as $k) {
            $this->{$k} = $o->{$k};
        }

        $this->sourceIpiNo = $o->internal_parts_inquiry_id
            ? InternalPartsInquiry::whereKey($o->internal_parts_inquiry_id)->value('ipi_no')
            : null;

        $this->items = $o->items->map(fn ($i) => [
            'id' => $i->id,
            'spare_id' => $i->spare_id,
            'uom_id' => $i->uom_id,
            'return_type_id' => $i->return_type_id,
            'description' => $i->description,
            'is_alternate' => (bool) $i->is_alternate,
            'qty_requested' => (float) $i->qty_requested,
            'qty_issued' => (float) $i->qty_issued,
            'qty_returned' => (float) $i->qty_returned,
            'stock_status' => $i->stock_status,
            'issue_status' => $i->issue_status,
            'return_status' => $i->return_status,
            'before_photo_path' => $i->before_photo_path,
            'after_photo_path' => $i->after_photo_path,
            'notes' => $i->notes,
            'sequence_no' => (int) $i->sequence_no,
        ])->all();
    }

    public function updated(string $name, $value): void
    {
        if ($name === 'customer_vehicle_id' && $value) {
            $vehicle = CustomerVehicleMaster::find($value);
            if ($vehicle) {
                $this->customer_id = $vehicle->customer_id;
            }
        }
        if (preg_match('/^items\.(\d+)\.spare_id$/', $name, $m) && $value) {
            $this->prefillSpare((int) $m[1], (int) $value);
        }
    }

    protected function prefillSpare(int $i, int $spareId): void
    {
        $spare = SpareMaster::find($spareId);
        if (! $spare) {
            return;
        }
        $this->items[$i]['description'] = $spare->name;
        $this->items[$i]['uom_id'] = $spare->uom_id;
        $qty = StockLedger::currentQty($spareId);
        $this->items[$i]['stock_status'] = $qty > 0 ? 'available' : 'out_of_stock';
    }

    // ── Scan to issue ─────────────────────────────────────────────────────────

    /** Part no / barcode typed or scanned into the issue box. */
    public string $scanCode = '';

    /**
     * What the pending scan would do, once confirmed.
     *
     * @var array{spare_id:int, name:string, code:?string, qty:float, on_hand:float, remaining:float, layers:list<array{batch_no:?string, expiry_date:?string, qty:float, rate:float, expired:bool}>, short:bool}|null
     */
    public ?array $pendingScan = null;

    /**
     * Resolve a scanned code and stage the issue for confirmation.
     *
     * Nothing moves here — the FIFO allocation is *previewed* so the store
     * person sees which batch is about to leave and what will be left before
     * committing. Confirming adds the line; the actual ledger write still
     * happens on save, through the same guarded path as a typed line.
     */
    public function scan(): void
    {
        $code = strtoupper(trim($this->scanCode));
        if ($code === '') {
            return;
        }

        $spare = SpareMaster::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereLike('spare_code', $code, caseSensitive: false)
                ->orWhereLike('name', $code, caseSensitive: false))
            ->first();

        if (! $spare) {
            $this->addError('scanCode', 'No active spare matches "'.$code.'".');

            return;
        }

        $this->resetErrorBag('scanCode');
        $this->pendingScan = $this->previewIssue($spare, 1.0);

        Flux::modal('scan-confirm')->show();
    }

    /** Nudge the pending quantity up or down before confirming. */
    public function setScanQty(float $qty): void
    {
        if (! $this->pendingScan || $qty < 1) {
            return;
        }

        $spare = SpareMaster::find($this->pendingScan['spare_id']);
        if ($spare) {
            $this->pendingScan = $this->previewIssue($spare, $qty);
        }
    }

    /**
     * Walk the same layers `StockIssuer` would, so the preview cannot drift
     * from what actually happens on save.
     *
     * @return array<string, mixed>
     */
    protected function previewIssue(SpareMaster $spare, float $qty): array
    {
        $onHand = StockLedger::currentQty($spare->id);

        $outstanding = $qty;
        $layers = [];
        foreach (StockLedger::openLayers($spare->id) as $layer) {
            if ($outstanding <= 0.0001) {
                break;
            }
            $take = min($outstanding, $layer['remaining']);
            $outstanding -= $take;
            $layers[] = [
                'batch_no' => $layer['batch_no'],
                'expiry_date' => $layer['expiry_date'] ? (string) $layer['expiry_date'] : null,
                'qty' => $take,
                'rate' => $layer['rate_per_unit'],
                'expired' => $layer['expiry_date'] !== null && Carbon::parse($layer['expiry_date'])->isPast(),
            ];
        }

        return [
            'spare_id' => $spare->id,
            'name' => $spare->name,
            'code' => $spare->spare_code,
            'qty' => $qty,
            'on_hand' => $onHand,
            'remaining' => $onHand - $qty,
            'layers' => $layers,
            'short' => $outstanding > 0.0001,
        ];
    }

    /** Confirmed: add (or top up) the line for the scanned spare. */
    public function confirmScan(): void
    {
        if (! $this->pendingScan) {
            return;
        }

        $spareId = $this->pendingScan['spare_id'];
        $qty = (float) $this->pendingScan['qty'];

        $existing = null;
        foreach ($this->items as $i => $item) {
            if ((int) ($item['spare_id'] ?? 0) === $spareId) {
                $existing = $i;
                break;
            }
        }

        if ($existing !== null) {
            $this->items[$existing]['qty_issued'] = (float) ($this->items[$existing]['qty_issued'] ?? 0) + $qty;
            $this->items[$existing]['qty_requested'] = max(
                (float) ($this->items[$existing]['qty_requested'] ?? 0),
                (float) $this->items[$existing]['qty_issued'],
            );
        } else {
            $this->addItem();
            $last = count($this->items) - 1;
            $this->items[$last]['spare_id'] = $spareId;
            $this->items[$last]['qty_requested'] = $qty;
            $this->items[$last]['qty_issued'] = $qty;
            $this->items[$last]['issue_status'] = 'issued';
            $this->prefillSpare($last, $spareId);
        }

        $this->cancelScan();
        Flux::toast(text: 'Added '.rtrim(rtrim(number_format($qty, 2), '0'), '.').' to the issue list.', variant: 'success');
    }

    public function cancelScan(): void
    {
        $this->pendingScan = null;
        $this->scanCode = '';
        Flux::modal('scan-confirm')->close();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'id' => null,
            'spare_id' => null,
            'uom_id' => null,
            'return_type_id' => null,
            'description' => '',
            'is_alternate' => false,
            'qty_requested' => 1,
            'qty_issued' => 0,
            'qty_returned' => 0,
            'stock_status' => 'available',
            'issue_status' => 'pending',
            'return_status' => null,
            'before_photo_path' => null,
            'after_photo_path' => null,
            'notes' => null,
            'sequence_no' => count($this->items) + 1,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index], $this->itemBeforeFiles[$index], $this->itemAfterFiles[$index]);
        $this->items = array_values($this->items);
    }

    public function clearItemPhoto(int $index, string $which): void
    {
        $field = $which === 'after' ? 'after_photo_path' : 'before_photo_path';
        if (! empty($this->items[$index][$field])) {
            Storage::disk('public')->delete($this->items[$index][$field]);
        }
        $this->items[$index][$field] = null;
        unset($this->{$which === 'after' ? 'itemAfterFiles' : 'itemBeforeFiles'}[$index]);
    }

    public function removeAttachment(int $id): void
    {
        if (! in_array($id, $this->removedAttachmentIds, true)) {
            $this->removedAttachmentIds[] = $id;
        }
    }

    protected function rules(): array
    {
        return [
            'internal_parts_inquiry_id' => ['nullable', 'integer', Rule::exists('internal_parts_inquiries', 'id')],
            'ipo_type' => ['required', Rule::in(array_keys(InternalPartOrder::ipoTypes()))],
            'priority_id' => ['nullable', 'integer', Rule::exists('priorities', 'id')->where('is_active', true)],
            'status' => ['required', Rule::in(array_keys(InternalPartOrder::statuses()))],
            'job_card_id' => ['nullable', 'integer', 'exists:job_cards,id'],
            'sales_estimate_id' => ['nullable', 'integer', 'exists:sales_estimates,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_vehicle_id' => ['nullable', 'integer', 'exists:customer_vehicles,id'],
            'department_id' => ['nullable', 'integer', 'exists:workshop_departments,id'],
            'service_type_id' => ['nullable', 'integer', 'exists:service_types,id'],
            'requested_by_id' => ['nullable', 'integer', 'exists:employees,id'],
            'technician_id' => ['nullable', 'integer', 'exists:employees,id'],
            'store_incharge_id' => ['nullable', 'integer', 'exists:employees,id'],
            'approval_authority' => ['nullable', Rule::in(array_keys(InternalPartOrder::approvalAuthorities()))],
            'approval_status' => ['required', Rule::in(array_keys(InternalPartOrder::approvalStatuses()))],
            'approved_by_id' => ['nullable', 'integer', 'exists:employees,id'],
            'rejection_reason_id' => ['nullable', 'integer', 'exists:ipo_rejection_reasons,id'],
            'cancellation_reason_id' => ['nullable', 'integer', 'exists:ipo_cancellation_reasons,id'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty_requested' => ['numeric', 'min:0.01'],
            'items.*.qty_issued' => ['numeric', 'min:0'],
            'items.*.qty_returned' => ['numeric', 'min:0'],
            'items.*.stock_status' => ['required', Rule::in(array_keys(InternalPartOrder::stockStatuses()))],
            'items.*.issue_status' => ['required', Rule::in(array_keys(InternalPartOrder::issueStatuses()))],
            'items.*.return_status' => ['nullable', Rule::in(array_keys(InternalPartOrder::returnStatuses()))],

            'itemBeforeFiles.*' => ['image', 'max:8192'],
            'itemAfterFiles.*' => ['image', 'max:8192'],
            'attachmentFiles.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
        ];
    }

    /** Parts-scoped urgency levels from the shared priority master. */
    #[Computed]
    public function priorities()
    {
        return PriorityMaster::forScope(PriorityMaster::APPLIES_PARTS)->get(['id', 'name']);
    }

    #[Computed]
    public function jobCards()
    {
        return JobCard::query()->orderByDesc('opened_at')->limit(100)->get(['id', 'job_card_no']);
    }

    #[Computed]
    public function estimates()
    {
        return SalesEstimate::query()->orderByDesc('created_at')->limit(100)->get(['id', 'estimate_no']);
    }

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
    public function returnTypes()
    {
        return ReturnTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function rejectionReasons()
    {
        return IpoRejectionReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function cancellationReasons()
    {
        return IpoCancellationReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function existingAttachments()
    {
        if (! $this->editingId) {
            return collect();
        }

        return InternalPartOrder::findOrFail($this->editingId)->attachments()
            ->whereNotIn('id', $this->removedAttachmentIds)
            ->get();
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'internal_part_order.update' : 'internal_part_order.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        unset($data['items'], $data['itemBeforeFiles'], $data['itemAfterFiles'], $data['attachmentFiles']);

        // Lifecycle stamps.
        $existing = $this->editingId ? InternalPartOrder::find($this->editingId) : null;
        if ($data['status'] === 'requested' && (! $existing || ! $existing->requested_at)) {
            $data['requested_at'] = now();
        }
        if ($data['approval_status'] === 'approved' && (! $existing || ! $existing->approved_at)) {
            $data['approved_at'] = now();
        }
        if (in_array($data['status'], ['partially_issued', 'fully_issued'], true)) {
            $data['issued_at'] = now();
        }

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }

        $isCreate = $this->editingId === null;

        $order = $this->runStockGuarded(fn () => DB::transaction(function () use ($data, $items, $isCreate) {
            if ($isCreate) {
                $row = InternalPartOrder::create($data);
                $this->editingId = $row->id;
                $this->order_no = $row->fresh()->order_no;
            } else {
                $row = InternalPartOrder::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncAttachments($row);
            $this->syncStockEntries($row);

            return $row;
        }));

        if ($order === null) {
            return null;
        }

        $this->itemBeforeFiles = [];
        $this->itemAfterFiles = [];
        $this->attachmentFiles = [];
        $this->removedAttachmentIds = [];

        Flux::toast(text: 'IPO '.$order->fresh()->order_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        if ($isCreate) {
            return redirect()->route('internal-part-order.edit', $order->id);
        }

        return redirect()->route('internal-part-order.index');
    }

    /**
     * Move the stock this order actually issued (and took back).
     *
     * Reverse-then-repost, so editing an issued order corrects the ledger
     * instead of stacking onto it. Draft and cancelled orders hold no stock —
     * parts are only committed once they leave the store.
     */
    protected function syncStockEntries(InternalPartOrder $order): void
    {
        StockIssuer::reverse($order);

        if (in_array($order->status, ['draft', 'cancelled'], true)) {
            return;
        }

        $movedAt = $order->issued_at ?? now();

        foreach ($order->items()->whereNotNull('spare_id')->get() as $item) {
            $issued = (float) $item->qty_issued;
            $returned = (float) $item->qty_returned;

            $issueEntries = $issued > 0
                ? StockIssuer::issue($item->spare_id, $issued, StockEntry::TYPE_IPO_ISSUE, $order, [
                    'moved_at' => $movedAt,
                    'notes' => 'IPO '.$order->order_no,
                ])
                : [];

            if ($returned <= 0) {
                continue;
            }

            // A part coming back re-enters at what it left at, not at today's
            // purchase price — otherwise an unused part would revalue stock.
            StockIssuer::receive(
                $item->spare_id,
                $returned,
                $this->averageIssueRate($issueEntries) ?: StockLedger::lastInwardRate($item->spare_id),
                StockEntry::TYPE_IPO_RETURN,
                $order,
                ['moved_at' => $movedAt, 'notes' => 'IPO '.$order->order_no.' return'],
            );
        }
    }

    /**
     * Weighted average rate of the entries an issue produced — an issue split
     * across FIFO layers leaves at more than one rate.
     *
     * @param  list<StockEntry>  $entries
     */
    protected function averageIssueRate(array $entries): float
    {
        $qty = array_sum(array_map(fn ($e) => abs((float) $e->qty), $entries));
        if ($qty <= 0) {
            return 0.0;
        }
        $value = array_sum(array_map(fn ($e) => abs((float) $e->qty) * (float) $e->rate_per_unit, $entries));

        return round($value / $qty, 2);
    }

    /** On an IPO the quantity leaving the store is `qty_issued`, not `qty`. */
    protected function stockErrorKey(int $spareId): string
    {
        foreach ($this->items as $index => $item) {
            if ((int) ($item['spare_id'] ?? 0) === $spareId) {
                return 'items.'.$index.'.qty_issued';
            }
        }

        return 'items';
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncItems(InternalPartOrder $order, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $local = $this->items[$i] ?? [];

            $beforePath = $local['before_photo_path'] ?? null;
            $afterPath = $local['after_photo_path'] ?? null;
            if (isset($this->itemBeforeFiles[$i]) && $this->itemBeforeFiles[$i] instanceof TemporaryUploadedFile) {
                if ($beforePath) {
                    Storage::disk('public')->delete($beforePath);
                }
                $beforePath = $this->itemBeforeFiles[$i]->store("internal-part-orders/{$order->id}/items", 'public');
            }
            if (isset($this->itemAfterFiles[$i]) && $this->itemAfterFiles[$i] instanceof TemporaryUploadedFile) {
                if ($afterPath) {
                    Storage::disk('public')->delete($afterPath);
                }
                $afterPath = $this->itemAfterFiles[$i]->store("internal-part-orders/{$order->id}/items", 'public');
            }

            $payload = [
                'spare_id' => $local['spare_id'] ?? null,
                'uom_id' => $local['uom_id'] ?? null,
                'return_type_id' => $local['return_type_id'] ?? null,
                'description' => strtoupper((string) $row['description']),
                'is_alternate' => (bool) ($local['is_alternate'] ?? false),
                'qty_requested' => (float) ($row['qty_requested'] ?? 1),
                'qty_issued' => (float) ($row['qty_issued'] ?? 0),
                'qty_returned' => (float) ($row['qty_returned'] ?? 0),
                'stock_status' => $row['stock_status'],
                'issue_status' => $row['issue_status'],
                'return_status' => $row['return_status'] ?? null,
                'before_photo_path' => $beforePath,
                'after_photo_path' => $afterPath,
                'notes' => isset($local['notes']) && is_string($local['notes']) ? strtoupper($local['notes']) : null,
                'sequence_no' => $i + 1,
            ];

            if (! empty($local['id'])) {
                $existing = $order->items()->whereKey($local['id'])->first();
                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;
                    $this->items[$i]['before_photo_path'] = $beforePath;
                    $this->items[$i]['after_photo_path'] = $afterPath;

                    continue;
                }
            }

            $created = $order->items()->create($payload);
            $keptIds[] = $created->id;
            $this->items[$i]['id'] = $created->id;
            $this->items[$i]['before_photo_path'] = $beforePath;
            $this->items[$i]['after_photo_path'] = $afterPath;
        }

        $order->items()->whereNotIn('id', $keptIds)->delete();
    }

    protected function syncAttachments(InternalPartOrder $order): void
    {
        if ($this->removedAttachmentIds) {
            foreach ($order->attachments()->whereIn('id', $this->removedAttachmentIds)->get() as $att) {
                Storage::disk('public')->delete($att->path);
                $att->delete();
            }
        }

        foreach ($this->attachmentFiles as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }
            $order->attachments()->create([
                'path' => $file->store("internal-part-orders/{$order->id}/attachments", 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        }
    }

    public function updatedJobCardId(): void
    {
        $this->prefillFromJobCard();
    }

    protected function prefillFromJobCard(): void
    {
        if (! $this->job_card_id) {
            return;
        }

        $jobCard = JobCard::find($this->job_card_id);

        if (! $jobCard) {
            return;
        }

        $this->customer_id = $jobCard->customer_id;
        $this->customer_vehicle_id = $jobCard->customer_vehicle_id;
        $this->department_id = $jobCard->workshop_department_id;
        $this->service_type_id = $jobCard->service_type_id;
        $this->technician_id = $jobCard->assigned_technician_id;
    }

    public function render()
    {
        return view('internal-part-order::edit');
    }
}
