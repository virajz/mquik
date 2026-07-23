<?php

namespace App\Modules\ChallanEntry\Livewire;

use App\Modules\ChallanEntry\Models\Challan;
use App\Modules\ChallanReasonMaster\Models\ChallanReasonMaster;
use App\Modules\ChargeTypeMaster\Models\ChargeTypeMaster;
use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\ItemRejectionReasonMaster\Models\ItemRejectionReasonMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\TransportModeMaster\Models\TransportModeMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
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
#[Title('Challan Entry')]
class Edit extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $challan_no = null;

    public string $purchase_type = 'stock';

    public ?string $discount_scheme = null;

    public string $inventory_status = 'spares_in_transit';

    public ?int $vendor_id = null;

    public ?int $transport_mode_id = null;

    public ?int $transport_company_id = null;

    public ?int $challan_reason_id = null;

    public ?int $job_card_id = null;

    public ?int $department_id = null;

    public ?int $advisor_id = null;

    public ?int $technician_id = null;

    public ?int $store_incharge_id = null;

    public ?int $driver_id = null;

    public ?string $po_reference = null;

    public ?string $challan_date = null;

    public ?string $notes = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    /** @var list<array<string, mixed>> */
    public array $items = [];

    /** @var list<array{id: ?int, charge_type_id: ?int, amount: float|string, notes: ?string}> */
    public array $charges = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachmentFiles = [];

    /** @var list<int> */
    public array $removedAttachmentIds = [];

    public function mount(?Challan $challan = null): void
    {
        if ($challan && $challan->exists) {
            $this->load($challan);
        }
    }

    protected function load(Challan $c): void
    {
        $c->load(['items', 'charges']);

        $this->editingId = $c->id;
        foreach ([
            'challan_no', 'purchase_type', 'discount_scheme', 'inventory_status', 'vendor_id', 'transport_mode_id',
            'transport_company_id', 'challan_reason_id', 'job_card_id', 'department_id', 'advisor_id', 'technician_id',
            'store_incharge_id', 'driver_id', 'po_reference', 'notes',
        ] as $k) {
            $this->{$k} = $c->{$k};
        }
        $this->challan_date = $c->challan_date?->format('Y-m-d');

        $this->items = $c->items->map(fn ($i) => [
            'id' => $i->id,
            'spare_id' => $i->spare_id,
            'uom_id' => $i->uom_id,
            'tax_id' => $i->tax_id,
            'rejection_reason_id' => $i->rejection_reason_id,
            'description' => $i->description,
            'hsn_code' => $i->hsn_code,
            'qty' => (float) $i->qty,
            'unit_rate' => (float) $i->unit_rate,
            'discount_value' => (float) $i->discount_value,
            'tax_percent' => (float) $i->tax_percent,
            'material_condition' => $i->material_condition,
            'invoice_status' => $i->invoice_status,
            'sequence_no' => (int) $i->sequence_no,
        ])->all();

        $this->charges = $c->charges->map(fn ($ch) => [
            'id' => $ch->id,
            'charge_type_id' => $ch->charge_type_id,
            'amount' => (float) $ch->amount,
            'notes' => $ch->notes,
        ])->all();
    }

    public function updated(string $name, $value): void
    {
        if (preg_match('/^items\.(\d+)\.spare_id$/', $name, $m) && $value) {
            $this->prefillSpare((int) $m[1], (int) $value);
        }
    }

    protected function prefillSpare(int $i, int $spareId): void
    {
        $spare = SpareMaster::with('tax')->find($spareId);
        if (! $spare) {
            return;
        }
        $this->items[$i]['description'] = $spare->name;
        $this->items[$i]['hsn_code'] = $spare->hsn_code;
        $this->items[$i]['uom_id'] = $spare->uom_id;
        $this->items[$i]['unit_rate'] = (float) $spare->rate_before_tax;
        $this->items[$i]['tax_id'] = $spare->tax_id;
        $this->items[$i]['tax_percent'] = (float) (($spare->tax?->gst_percent ?? 0) + ($spare->tax?->cess_percent ?? 0));
    }

    public function addLine(): void
    {
        $this->items[] = [
            'id' => null,
            'spare_id' => null,
            'uom_id' => null,
            'tax_id' => null,
            'rejection_reason_id' => null,
            'description' => '',
            'hsn_code' => null,
            'qty' => 1,
            'unit_rate' => 0,
            'discount_value' => 0,
            'tax_percent' => 0,
            'material_condition' => 'new',
            'invoice_status' => 'pending',
            'sequence_no' => count($this->items) + 1,
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function addCharge(): void
    {
        $this->charges[] = ['id' => null, 'charge_type_id' => null, 'amount' => 0, 'notes' => null];
    }

    public function removeCharge(int $index): void
    {
        unset($this->charges[$index]);
        $this->charges = array_values($this->charges);
    }

    public function removeAttachment(int $id): void
    {
        if (! in_array($id, $this->removedAttachmentIds, true)) {
            $this->removedAttachmentIds[] = $id;
        }
    }

    /**
     * @return array{parts: float, discount: float, tax: float, charges: float, grand: float}
     */
    #[Computed]
    public function totals(): array
    {
        $parts = 0.0;
        $discount = 0.0;
        $tax = 0.0;
        foreach ($this->items as $row) {
            $base = (float) ($row['qty'] ?? 0) * (float) ($row['unit_rate'] ?? 0);
            $disc = min((float) ($row['discount_value'] ?? 0), $base);
            $taxable = $base - $disc;
            $parts += $base;
            $discount += $disc;
            $tax += $taxable * (float) ($row['tax_percent'] ?? 0) / 100;
        }
        $charges = 0.0;
        foreach ($this->charges as $ch) {
            $charges += (float) ($ch['amount'] ?? 0);
        }

        return [
            'parts' => round($parts, 2),
            'discount' => round($discount, 2),
            'tax' => round($tax, 2),
            'charges' => round($charges, 2),
            'grand' => round($parts - $discount + $tax + $charges, 2),
        ];
    }

    protected function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'purchase_type' => ['required', Rule::in(array_keys(Challan::purchaseTypes()))],
            'discount_scheme' => ['nullable', Rule::in(array_keys(Challan::discountSchemes()))],
            'inventory_status' => ['required', Rule::in(array_keys(Challan::inventoryStatuses()))],
            'transport_mode_id' => ['nullable', 'integer', 'exists:transport_modes,id'],
            'transport_company_id' => ['nullable', 'integer', 'exists:courier_companies,id'],
            'challan_reason_id' => ['nullable', 'integer', 'exists:challan_reasons,id'],
            'job_card_id' => ['nullable', 'integer', 'exists:job_cards,id'],
            'department_id' => ['nullable', 'integer', 'exists:workshop_departments,id'],
            'advisor_id' => ['nullable', 'integer', 'exists:employees,id'],
            'technician_id' => ['nullable', 'integer', 'exists:employees,id'],
            'store_incharge_id' => ['nullable', 'integer', 'exists:employees,id'],
            'driver_id' => ['nullable', 'integer', 'exists:employees,id'],
            'po_reference' => ['nullable', 'string', 'max:60'],
            'challan_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['numeric', 'min:0.01'],
            'items.*.unit_rate' => ['numeric', 'min:0'],
            'items.*.discount_value' => ['numeric', 'min:0'],
            'items.*.tax_percent' => ['numeric', 'min:0', 'max:100'],
            'items.*.material_condition' => ['required', Rule::in(array_keys(Challan::materialConditions()))],
            'items.*.invoice_status' => ['required', Rule::in(array_keys(Challan::invoiceStatuses()))],

            'charges' => ['array'],
            'charges.*.charge_type_id' => ['nullable', 'integer', 'exists:charge_types,id'],
            'charges.*.amount' => ['numeric', 'min:0'],
            'charges.*.notes' => ['nullable', 'string', 'max:255'],

            'attachmentFiles.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
        ];
    }

    #[Computed]
    public function vendors()
    {
        return VendorMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
    public function challanReasons()
    {
        return ChallanReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function jobCards()
    {
        return JobCard::query()->orderByDesc('opened_at')->limit(100)->get(['id', 'job_card_no']);
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
    public function rejectionReasons()
    {
        return ItemRejectionReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function chargeTypes()
    {
        return ChargeTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function existingAttachments()
    {
        if (! $this->editingId) {
            return collect();
        }

        return Challan::findOrFail($this->editingId)->attachments()
            ->whereNotIn('id', $this->removedAttachmentIds)
            ->get();
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'challan_entry.update' : 'challan_entry.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $charges = $data['charges'] ?? [];
        unset($data['items'], $data['charges'], $data['attachmentFiles']);

        $totals = $this->totals();
        $data['parts_total'] = $totals['parts'];
        $data['discount_total'] = $totals['discount'];
        $data['tax_total'] = $totals['tax'];
        $data['charges_total'] = $totals['charges'];
        $data['grand_total'] = $totals['grand'];

        foreach (['po_reference', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $challan = DB::transaction(function () use ($data, $items, $charges, $isCreate) {
            if ($isCreate) {
                $row = Challan::create($data);
                $this->editingId = $row->id;
                $this->challan_no = $row->fresh()->challan_no;
            } else {
                $row = Challan::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncCharges($row, $charges);
            $this->syncAttachments($row);

            return $row;
        });

        $this->attachmentFiles = [];
        $this->removedAttachmentIds = [];

        Flux::toast(text: 'Challan '.$challan->fresh()->challan_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        if ($isCreate) {
            return redirect()->route('challan-entry.edit', $challan->id);
        }

        return redirect()->route('challan-entry.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncItems(Challan $challan, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $local = $this->items[$i] ?? [];
            $base = (float) ($row['qty'] ?? 0) * (float) ($row['unit_rate'] ?? 0);
            $disc = min((float) ($row['discount_value'] ?? 0), $base);
            $lineTotal = round(($base - $disc) * (1 + (float) ($row['tax_percent'] ?? 0) / 100), 2);

            $payload = [
                'spare_id' => $local['spare_id'] ?? null,
                'uom_id' => $local['uom_id'] ?? null,
                'tax_id' => $local['tax_id'] ?? null,
                'rejection_reason_id' => $local['rejection_reason_id'] ?? null,
                'description' => strtoupper((string) $row['description']),
                'hsn_code' => $local['hsn_code'] ?? null,
                'qty' => (float) ($row['qty'] ?? 1),
                'unit_rate' => (float) ($row['unit_rate'] ?? 0),
                'discount_value' => (float) ($row['discount_value'] ?? 0),
                'tax_percent' => (float) ($row['tax_percent'] ?? 0),
                'line_total' => $lineTotal,
                'material_condition' => $row['material_condition'],
                'invoice_status' => $row['invoice_status'],
                'sequence_no' => $i + 1,
            ];

            if (! empty($local['id'])) {
                $existing = $challan->items()->whereKey($local['id'])->first();
                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $created = $challan->items()->create($payload);
            $keptIds[] = $created->id;
            $this->items[$i]['id'] = $created->id;
        }

        $challan->items()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncCharges(Challan $challan, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $local = $this->charges[$i] ?? [];
            $payload = [
                'charge_type_id' => $local['charge_type_id'] ?? null,
                'amount' => (float) ($row['amount'] ?? 0),
                'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
                'sequence_no' => $i + 1,
            ];

            if (! empty($local['id'])) {
                $existing = $challan->charges()->whereKey($local['id'])->first();
                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $created = $challan->charges()->create($payload);
            $keptIds[] = $created->id;
            $this->charges[$i]['id'] = $created->id;
        }

        $challan->charges()->whereNotIn('id', $keptIds)->delete();
    }

    protected function syncAttachments(Challan $challan): void
    {
        if ($this->removedAttachmentIds) {
            foreach ($challan->attachments()->whereIn('id', $this->removedAttachmentIds)->get() as $att) {
                Storage::disk('public')->delete($att->path);
                $att->delete();
            }
        }

        foreach ($this->attachmentFiles as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }
            $challan->attachments()->create([
                'path' => $file->store("challans/{$challan->id}/attachments", 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        }
    }

    public function render()
    {
        return view('challan-entry::edit');
    }
}
