<?php

namespace App\Modules\SalesEstimate\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DamageCauseMaster\Models\DamageCauseMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\EstimateRevisionReasonMaster\Models\EstimateRevisionReasonMaster;
use App\Modules\EstimateTemplateMaster\Models\EstimateTemplateMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
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
#[Title('Sales Estimate')]
class Edit extends Component
{
    use SearchesPickerOptions;

    /** Search term for the server-backed inventoryGroups picker. */
    public string $inventoryGroupSearch = '';

    public ?int $editingId = null;

    public ?string $estimate_no = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $job_card_id = null;

    public string $estimate_type = 'before';

    public string $parts_category = 'any';

    public string $status = 'pending';

    public ?int $damage_cause_id = null;

    public ?int $department_id = null;

    public ?int $service_type_id = null;

    public ?int $advisor_id = null;

    public ?int $technician_id = null;

    public ?int $insurance_company_id = null;

    public ?string $policy_no = null;

    public ?int $service_package_id = null;

    public ?int $estimate_template_id = null;

    public ?int $old_estimate_id = null;

    public ?int $revision_reason_id = null;

    public string $labour_price_tier = 'retail';

    public ?string $discount_type = null;

    public float $discount_value = 0;

    public ?string $notes = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    #[Url(as: 'from-job-card')]
    public ?int $fromJobCard = null;

    /** @var list<array<string, mixed>> */
    public array $items = [];

    public function mount(?SalesEstimate $salesEstimate = null): void
    {
        if ($salesEstimate && $salesEstimate->exists) {
            $this->load($salesEstimate);

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
    }

    protected function load(SalesEstimate $e): void
    {
        $e->load('items');

        $this->editingId = $e->id;
        foreach ([
            'estimate_no', 'customer_id', 'customer_vehicle_id', 'job_card_id', 'estimate_type', 'parts_category',
            'status', 'damage_cause_id', 'department_id', 'service_type_id', 'advisor_id', 'technician_id',
            'insurance_company_id', 'policy_no', 'service_package_id', 'estimate_template_id', 'old_estimate_id',
            'revision_reason_id', 'labour_price_tier', 'discount_type', 'notes',
        ] as $k) {
            $this->{$k} = $e->{$k};
        }
        $this->discount_value = (float) $e->discount_value;

        $this->items = $e->items->map(fn ($i) => [
            'id' => $i->id,
            'line_type' => $i->line_type,
            'spare_id' => $i->spare_id,
            'labour_id' => $i->labour_id,
            'inventory_group_id' => $i->inventory_group_id,
            'tax_id' => $i->tax_id,
            'description' => $i->description,
            'hsn_code' => $i->hsn_code,
            'qty' => (float) $i->qty,
            'unit_rate' => (float) $i->unit_rate,
            'tax_percent' => (float) $i->tax_percent,
            'is_insurance_approved' => (bool) $i->is_insurance_approved,
            'sequence_no' => (int) $i->sequence_no,
        ])->all();
    }

    public function updated(string $name, $value): void
    {
        if ($name === 'customer_vehicle_id') {
            $this->customer_id = $value
                ? CustomerVehicleMaster::whereKey($value)->value('customer_id')
                : null;

            return;
        }
        if (preg_match('/^items\.(\d+)\.spare_id$/', $name, $m) && $value) {
            $this->prefillSpare((int) $m[1], (int) $value);
        }
        if (preg_match('/^items\.(\d+)\.labour_id$/', $name, $m) && $value) {
            $this->prefillLabour((int) $m[1], (int) $value);
        }
        if (preg_match('/^items\.(\d+)\.tax_id$/', $name, $m)) {
            $this->syncLineTax((int) $m[1], $value ? (int) $value : null);
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
        $this->items[$i]['inventory_group_id'] = $spare->inventory_group_id;
        $this->items[$i]['unit_rate'] = (float) $spare->rate_before_tax;
        $this->items[$i]['tax_id'] = $spare->tax_id;
        $this->items[$i]['tax_percent'] = (float) (($spare->tax?->gst_percent ?? 0) + ($spare->tax?->cess_percent ?? 0));
    }

    protected function prefillLabour(int $i, int $labourId): void
    {
        $labour = LabourMaster::with('tax')->find($labourId);
        if (! $labour) {
            return;
        }
        $this->items[$i]['description'] = $labour->name;
        $this->items[$i]['hsn_code'] = $labour->hsn_sac_code;
        $this->items[$i]['inventory_group_id'] = $labour->inventory_group_id;
        $this->items[$i]['unit_rate'] = (float) $labour->rate_before_tax;
        $this->items[$i]['tax_id'] = $labour->tax_id;
        $this->items[$i]['tax_percent'] = (float) (($labour->tax?->gst_percent ?? 0) + ($labour->tax?->cess_percent ?? 0));
    }

    protected function syncLineTax(int $i, ?int $taxId): void
    {
        $tax = $taxId ? TaxMaster::find($taxId) : null;
        $this->items[$i]['tax_percent'] = (float) (($tax?->gst_percent ?? 0) + ($tax?->cess_percent ?? 0));
    }

    public function addLine(string $type): void
    {
        $this->items[] = [
            'id' => null,
            'line_type' => $type,
            'spare_id' => null,
            'labour_id' => null,
            'inventory_group_id' => null,
            'tax_id' => null,
            'description' => '',
            'hsn_code' => null,
            'qty' => 1,
            'unit_rate' => 0,
            'tax_percent' => 0,
            'is_insurance_approved' => false,
            'sequence_no' => count($this->items) + 1,
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /**
     * Snapshot the chosen template's lines into the estimate, pulling live rates.
     */
    public function applyTemplate(): void
    {
        if (! $this->estimate_template_id) {
            return;
        }
        $template = EstimateTemplateMaster::with('items')->find($this->estimate_template_id);
        if (! $template) {
            return;
        }

        foreach ($template->items as $ti) {
            $this->addLine($ti->line_type);
            $idx = count($this->items) - 1;
            $this->items[$idx]['qty'] = (float) $ti->default_qty;
            if ($ti->line_type === 'spare' && $ti->spare_id) {
                $this->items[$idx]['spare_id'] = $ti->spare_id;
                $this->prefillSpare($idx, (int) $ti->spare_id);
            } elseif ($ti->line_type === 'labour' && $ti->labour_id) {
                $this->items[$idx]['labour_id'] = $ti->labour_id;
                $this->prefillLabour($idx, (int) $ti->labour_id);
            }
        }

        Flux::toast(text: 'Template lines added.', variant: 'success');
    }

    /**
     * @return array{parts: float, labour: float, subtotal: float, discount: float, tax: float, grand: float, pass: float}
     */
    #[Computed]
    public function totals(): array
    {
        $parts = 0.0;
        $labour = 0.0;
        $tax = 0.0;
        $approvedBase = 0.0;

        foreach ($this->items as $row) {
            $base = (float) ($row['qty'] ?? 0) * (float) ($row['unit_rate'] ?? 0);
            $lineTax = $base * (float) ($row['tax_percent'] ?? 0) / 100;
            $tax += $lineTax;
            if (($row['line_type'] ?? 'spare') === 'labour') {
                $labour += $base;
            } else {
                $parts += $base;
            }
            if (! empty($row['is_insurance_approved'])) {
                $approvedBase += $base;
            }
        }

        $subtotal = $parts + $labour;
        $discount = match ($this->discount_type) {
            'flat' => (float) $this->discount_value,
            'percentage', 'on_mrp', 'on_rcp' => round($subtotal * (float) $this->discount_value / 100, 2),
            default => 0.0,
        };
        $discount = min($discount, $subtotal);
        $grand = round($subtotal + $tax - $discount, 2);
        $pass = $subtotal > 0 ? round($approvedBase / $subtotal * 100, 2) : 0.0;

        return [
            'parts' => round($parts, 2),
            'labour' => round($labour, 2),
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'tax' => round($tax, 2),
            'grand' => $grand,
            'pass' => $pass,
        ];
    }

    protected function rules(): array
    {
        return [
            'customer_vehicle_id' => ['required', 'integer', 'exists:customer_vehicles,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'job_card_id' => ['nullable', 'integer', 'exists:job_cards,id'],
            'estimate_type' => ['required', Rule::in(array_keys(SalesEstimate::estimateTypes()))],
            'parts_category' => ['required', Rule::in(array_keys(SalesEstimate::partsCategories()))],
            'status' => ['required', Rule::in(array_keys(SalesEstimate::statuses()))],
            'damage_cause_id' => ['nullable', 'integer', 'exists:damage_causes,id'],
            'department_id' => ['nullable', 'integer', 'exists:workshop_departments,id'],
            'service_type_id' => ['nullable', 'integer', 'exists:service_types,id'],
            'advisor_id' => ['nullable', 'integer', 'exists:employees,id'],
            'technician_id' => ['nullable', 'integer', 'exists:employees,id'],
            'insurance_company_id' => ['nullable', 'integer', 'exists:insurance_companies,id'],
            'policy_no' => ['nullable', 'string', 'max:60'],
            'service_package_id' => ['nullable', 'integer', 'exists:service_packages,id'],
            'estimate_template_id' => ['nullable', 'integer', 'exists:estimate_templates,id'],
            'revision_reason_id' => ['nullable', 'integer', 'exists:estimate_revision_reasons,id'],
            'labour_price_tier' => ['required', Rule::in(array_keys(SalesEstimate::labourPriceTiers()))],
            'discount_type' => ['nullable', Rule::in(array_keys(SalesEstimate::discountTypes()))],
            'discount_value' => ['numeric', 'min:0'],
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
    public function vehiclePickerOptions()
    {
        $rows = CustomerVehicleMaster::query()
            ->with(['model.brand:id,name', 'customer:id,first_name,last_name'])
            ->where('is_active', true)
            ->orderByDesc('id')->limit(300)
            ->get(['id', 'registration_no', 'model_id', 'customer_id']);

        if ($this->customer_vehicle_id && ! $rows->contains('id', $this->customer_vehicle_id)) {
            $sel = CustomerVehicleMaster::with(['model.brand:id,name', 'customer:id,first_name,last_name'])->find($this->customer_vehicle_id);
            if ($sel) {
                $rows->prepend($sel);
            }
        }

        return $rows->map(fn ($v) => [
            'id' => $v->id,
            'label' => trim(($v->model?->brand?->name ?? '').' '.($v->model?->name ?? '')).' — '.$v->registration_no
                .' · '.trim($v->customer?->first_name.' '.($v->customer?->last_name ?? '')),
        ]);
    }

    #[Computed]
    public function jobCards()
    {
        return JobCard::query()->orderByDesc('opened_at')->limit(100)->get(['id', 'job_card_no']);
    }

    #[Computed]
    public function damageCauses()
    {
        return DamageCauseMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
    public function insuranceCompanies()
    {
        return InsuranceCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function servicePackages()
    {
        return ServicePackageMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function templates()
    {
        return EstimateTemplateMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function revisionReasons()
    {
        return EstimateRevisionReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
    public function taxes()
    {
        return TaxMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'gst_percent', 'cess_percent']);
    }

    #[Computed]
    public function inventoryGroups()
    {
        return $this->pickerOptions(
            query: InventoryGroupMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'code'],
            term: $this->inventoryGroupSearch,
            selected: $this->inventory_group_id,
            columns: ['id', 'name', 'parent_id'],
            limit: 30,
        );
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'sales_estimate.update' : 'sales_estimate.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $totals = $this->totals();
        $data['parts_total'] = $totals['parts'];
        $data['labour_total'] = $totals['labour'];
        $data['discount_total'] = $totals['discount'];
        $data['tax_total'] = $totals['tax'];
        $data['grand_total'] = $totals['grand'];
        $data['insurance_pass_percent'] = $totals['pass'];

        // Lifecycle stamps on status transitions.
        $existing = $this->editingId ? SalesEstimate::find($this->editingId) : null;
        if ($data['status'] === 'prepared' && (! $existing || ! $existing->prepared_at)) {
            $data['prepared_at'] = now();
        }
        if (in_array($data['status'], ['sent_customer', 'sent_insurance'], true) && (! $existing || ! $existing->sent_at)) {
            $data['sent_at'] = now();
        }
        if (in_array($data['status'], ['approved', 'partially_approved'], true)) {
            $data['approved_at'] = now();
        }

        if (isset($data['policy_no']) && is_string($data['policy_no'])) {
            $data['policy_no'] = strtoupper($data['policy_no']);
        }
        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }

        $isCreate = $this->editingId === null;

        $estimate = DB::transaction(function () use ($data, $items, $isCreate) {
            if ($isCreate) {
                $row = SalesEstimate::create($data);
                $this->editingId = $row->id;
                $this->estimate_no = $row->fresh()->estimate_no;
            } else {
                $row = SalesEstimate::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);

            return $row;
        });

        Flux::toast(text: 'Estimate '.$estimate->fresh()->estimate_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        if ($isCreate) {
            return redirect()->route('sales-estimate.edit', $estimate->id);
        }

        return redirect()->route('sales-estimate.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncItems(SalesEstimate $estimate, array $rows): void
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
                'inventory_group_id' => $local['inventory_group_id'] ?? null,
                'tax_id' => $local['tax_id'] ?? null,
                'description' => strtoupper((string) $row['description']),
                'hsn_code' => $local['hsn_code'] ?? null,
                'qty' => (float) ($row['qty'] ?? 1),
                'unit_rate' => (float) ($row['unit_rate'] ?? 0),
                'tax_percent' => (float) ($row['tax_percent'] ?? 0),
                'line_total' => $lineTotal,
                'is_insurance_approved' => (bool) ($local['is_insurance_approved'] ?? false),
                'sequence_no' => $i + 1,
            ];

            if (! empty($local['id'])) {
                $existing = $estimate->items()->whereKey($local['id'])->first();
                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $created = $estimate->items()->create($payload);
            $keptIds[] = $created->id;
            $this->items[$i]['id'] = $created->id;
        }

        $estimate->items()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Revise: clone this estimate into a new draft linked back via old_estimate_id,
     * mark the current one Revised.
     */
    public function revise()
    {
        $this->authorize('sales_estimate.create');

        if (! $this->editingId) {
            return null;
        }

        $source = SalesEstimate::with('items')->findOrFail($this->editingId);

        $new = DB::transaction(function () use ($source) {
            $clone = $source->replicate([
                'estimate_no', 'status', 'prepared_at', 'sent_at', 'approved_at',
                'insurance_pass_percent', 'created_at', 'updated_at',
            ]);
            $clone->status = 'pending';
            $clone->old_estimate_id = $source->id;
            $clone->estimate_no = null;
            $clone->save();

            foreach ($source->items as $item) {
                $copy = $item->replicate(['sales_estimate_id', 'is_insurance_approved', 'created_at', 'updated_at']);
                $copy->sales_estimate_id = $clone->id;
                $copy->is_insurance_approved = false;
                $copy->save();
            }

            $source->update(['status' => 'revised']);

            return $clone;
        });

        Flux::toast(text: 'Revision '.$new->fresh()->estimate_no.' created from '.$source->estimate_no.'.', variant: 'success');

        return redirect()->route('sales-estimate.edit', $new->id);
    }

    public function render()
    {
        return view('sales-estimate::edit');
    }
}
