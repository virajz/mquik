<?php

namespace App\Modules\Consumable\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\ChallanEntry\Models\Challan;
use App\Modules\Consumable\Models\Consumable;
use App\Modules\ConsumableCategoryMaster\Models\ConsumableCategoryMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\Inventory\Models\StockEntry;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\LossReasonMaster\Models\LossReasonMaster;
use App\Modules\LossTypeMaster\Models\LossTypeMaster;
use App\Modules\PurchaseEntry\Models\PurchaseEntry;
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

#[Layout('layouts.app')]
#[Title('Consumable')]
class Edit extends Component
{
    use SearchesPickerOptions;

    /** Search term for the server-backed vendors picker. */
    public string $vendorSearch = '';

    public ?int $editingId = null;

    public ?string $consumable_no = null;

    public ?int $consumable_category_id = null;

    public ?int $job_card_id = null;

    public ?int $challan_id = null;

    public ?int $purchase_entry_id = null;

    public ?int $loss_type_id = null;

    public ?int $loss_reason_id = null;

    public ?int $department_id = null;

    public ?int $advisor_id = null;

    public ?int $technician_id = null;

    public ?int $store_incharge_id = null;

    public ?int $vendor_id = null;

    public ?string $approval_authority = null;

    public string $approval_status = 'requested';

    public ?string $communication_mode = null;

    public ?string $notes = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    #[Url(as: 'from-job-card')]
    public ?int $fromJobCard = null;

    /** @var list<array<string, mixed>> */
    public array $items = [];

    public function mount(?Consumable $consumable = null): void
    {
        if ($consumable && $consumable->exists) {
            $this->load($consumable);

            return;
        }

        if ($this->fromJobCard) {
            $this->job_card_id = $this->fromJobCard;
        }
    }

    protected function load(Consumable $c): void
    {
        $c->load('items');

        $this->editingId = $c->id;
        foreach ([
            'consumable_no', 'consumable_category_id', 'job_card_id', 'challan_id', 'purchase_entry_id', 'loss_type_id',
            'loss_reason_id', 'department_id', 'advisor_id', 'technician_id', 'store_incharge_id', 'vendor_id',
            'approval_authority', 'approval_status', 'communication_mode', 'notes',
        ] as $k) {
            $this->{$k} = $c->{$k};
        }

        $this->items = $c->items->map(fn ($i) => [
            'id' => $i->id,
            'line_type' => $i->line_type,
            'spare_id' => $i->spare_id,
            'labour_id' => $i->labour_id,
            'uom_id' => $i->uom_id,
            'tax_id' => $i->tax_id,
            'description' => $i->description,
            'hsn_code' => $i->hsn_code,
            'qty' => (float) $i->qty,
            'unit_rate' => (float) $i->unit_rate,
            'tax_percent' => (float) $i->tax_percent,
            'sequence_no' => (int) $i->sequence_no,
        ])->all();
    }

    public function updated(string $name, $value): void
    {
        if (preg_match('/^items\.(\d+)\.spare_id$/', $name, $m) && $value) {
            $this->prefill((int) $m[1], SpareMaster::with('tax')->find($value), 'hsn_code');
        }
        if (preg_match('/^items\.(\d+)\.labour_id$/', $name, $m) && $value) {
            $this->prefill((int) $m[1], LabourMaster::with('tax')->find($value), 'hsn_sac_code');
        }
    }

    protected function prefill(int $i, $model, string $hsnField): void
    {
        if (! $model) {
            return;
        }
        $this->items[$i]['description'] = $model->name;
        $this->items[$i]['hsn_code'] = $model->{$hsnField};
        $this->items[$i]['uom_id'] = $model->uom_id ?? null;
        $this->items[$i]['unit_rate'] = (float) $model->rate_before_tax;
        $this->items[$i]['tax_id'] = $model->tax_id;
        $this->items[$i]['tax_percent'] = (float) (($model->tax?->gst_percent ?? 0) + ($model->tax?->cess_percent ?? 0));
    }

    public function addLine(string $type): void
    {
        $this->items[] = [
            'id' => null, 'line_type' => $type, 'spare_id' => null, 'labour_id' => null, 'uom_id' => null,
            'tax_id' => null, 'description' => '', 'hsn_code' => null, 'qty' => 1, 'unit_rate' => 0, 'tax_percent' => 0,
            'sequence_no' => count($this->items) + 1,
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /**
     * @return array{parts: float, labour: float, tax: float, total: float}
     */
    #[Computed]
    public function totals(): array
    {
        $parts = 0.0;
        $labour = 0.0;
        $tax = 0.0;
        foreach ($this->items as $row) {
            $base = (float) ($row['qty'] ?? 0) * (float) ($row['unit_rate'] ?? 0);
            $tax += $base * (float) ($row['tax_percent'] ?? 0) / 100;
            if (($row['line_type'] ?? 'spare') === 'labour') {
                $labour += $base;
            } else {
                $parts += $base;
            }
        }

        return [
            'parts' => round($parts, 2),
            'labour' => round($labour, 2),
            'tax' => round($tax, 2),
            'total' => round($parts + $labour + $tax, 2),
        ];
    }

    protected function rules(): array
    {
        return [
            'consumable_category_id' => ['nullable', 'integer', 'exists:consumable_categories,id'],
            'job_card_id' => ['nullable', 'integer', 'exists:job_cards,id'],
            'challan_id' => ['nullable', 'integer', 'exists:challans,id'],
            'purchase_entry_id' => ['nullable', 'integer', 'exists:purchase_entries,id'],
            'loss_type_id' => ['nullable', 'integer', 'exists:loss_types,id'],
            'loss_reason_id' => ['nullable', 'integer', 'exists:loss_reasons,id'],
            'department_id' => ['nullable', 'integer', 'exists:workshop_departments,id'],
            'advisor_id' => ['nullable', 'integer', 'exists:employees,id'],
            'technician_id' => ['nullable', 'integer', 'exists:employees,id'],
            'store_incharge_id' => ['nullable', 'integer', 'exists:employees,id'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'approval_authority' => ['nullable', Rule::in(array_keys(Consumable::approvalAuthorities()))],
            'approval_status' => ['required', Rule::in(array_keys(Consumable::approvalStatuses()))],
            'communication_mode' => ['nullable', Rule::in(array_keys(Consumable::communicationModes()))],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array'],
            'items.*.line_type' => ['required', 'in:spare,labour'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['numeric', 'min:0.01'],
            'items.*.unit_rate' => ['numeric', 'min:0'],
            'items.*.tax_percent' => ['numeric', 'min:0', 'max:100'],
        ];
    }

    #[Computed]
    public function categories()
    {
        return ConsumableCategoryMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function jobCards()
    {
        return JobCard::query()->orderByDesc('opened_at')->limit(100)->get(['id', 'job_card_no']);
    }

    #[Computed]
    public function challans()
    {
        return Challan::query()->orderByDesc('created_at')->limit(100)->get(['id', 'challan_no']);
    }

    #[Computed]
    public function purchaseEntries()
    {
        return PurchaseEntry::query()->orderByDesc('created_at')->limit(100)->get(['id', 'purchase_no']);
    }

    #[Computed]
    public function lossTypes()
    {
        return LossTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function lossReasons()
    {
        return LossReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

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
    public function spares()
    {
        return SpareMaster::query()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name']);
    }

    #[Computed]
    public function labours()
    {
        return LabourMaster::query()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name']);
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

    public function save()
    {
        $this->authorize($this->editingId ? 'consumable.update' : 'consumable.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $totals = $this->totals();
        $data['parts_value'] = $totals['parts'];
        $data['labour_value'] = $totals['labour'];
        $data['tax_total'] = $totals['tax'];
        $data['total_value'] = $totals['total'];
        $data['consumed_at'] = $data['consumed_at'] ?? now();
        if ($data['approval_status'] === 'approved') {
            $data['approved_at'] = now();
        }

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }

        $isCreate = $this->editingId === null;

        $consumable = DB::transaction(function () use ($data, $items, $isCreate) {
            if ($isCreate) {
                $row = Consumable::create($data);
                $this->editingId = $row->id;
                $this->consumable_no = $row->fresh()->consumable_no;
            } else {
                $row = Consumable::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncStockDeductions($row);

            return $row;
        });

        Flux::toast(text: 'Consumable '.$consumable->fresh()->consumable_no.($isCreate ? ' recorded — stock deducted.' : ' updated — stock re-synced.'), variant: 'success');

        if ($isCreate) {
            return redirect()->route('consumable.edit', $consumable->id);
        }

        return redirect()->route('consumable.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncItems(Consumable $consumable, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $local = $this->items[$i] ?? [];
            $base = (float) ($row['qty'] ?? 0) * (float) ($row['unit_rate'] ?? 0);
            $lineTotal = round($base * (1 + (float) ($row['tax_percent'] ?? 0) / 100), 2);

            $payload = [
                'line_type' => $row['line_type'],
                'spare_id' => $row['line_type'] === 'spare' ? ($local['spare_id'] ?? null) : null,
                'labour_id' => $row['line_type'] === 'labour' ? ($local['labour_id'] ?? null) : null,
                'uom_id' => $local['uom_id'] ?? null,
                'tax_id' => $local['tax_id'] ?? null,
                'description' => strtoupper((string) $row['description']),
                'hsn_code' => $local['hsn_code'] ?? null,
                'qty' => (float) ($row['qty'] ?? 1),
                'unit_rate' => (float) ($row['unit_rate'] ?? 0),
                'tax_percent' => (float) ($row['tax_percent'] ?? 0),
                'line_total' => $lineTotal,
                'sequence_no' => $i + 1,
            ];

            if (! empty($local['id'])) {
                $ex = $consumable->items()->whereKey($local['id'])->first();
                if ($ex) {
                    $ex->update($payload);
                    $keptIds[] = $ex->id;

                    continue;
                }
            }

            $created = $consumable->items()->create($payload);
            $keptIds[] = $created->id;
            $this->items[$i]['id'] = $created->id;
        }

        $consumable->items()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Auto-deduct stock: every spare-linked line creates a negative (OUT)
     * consumption entry. Re-synced on each save so quantities never drift.
     */
    protected function syncStockDeductions(Consumable $consumable): void
    {
        StockEntry::query()
            ->where('source_type', Consumable::class)
            ->where('source_id', $consumable->id)
            ->delete();

        $movedAt = $consumable->consumed_at ?? now();

        foreach ($consumable->items()->where('line_type', 'spare')->whereNotNull('spare_id')->get() as $item) {
            if ((float) $item->qty <= 0) {
                continue;
            }
            StockEntry::create([
                'spare_id' => $item->spare_id,
                'entry_type' => StockEntry::TYPE_CONSUMPTION,
                'source_type' => Consumable::class,
                'source_id' => $consumable->id,
                'qty' => -1 * (float) $item->qty,
                'rate_per_unit' => (float) $item->unit_rate,
                'moved_at' => $movedAt,
                'actor_user_id' => auth()->id(),
                'notes' => 'Consumable '.$consumable->consumable_no,
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

        $this->department_id = $jobCard->workshop_department_id;
        $this->advisor_id = $jobCard->assigned_advisor_id;
        $this->technician_id = $jobCard->assigned_technician_id;
    }

    public function render()
    {
        return view('consumable::edit');
    }
}
