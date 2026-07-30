<?php

namespace App\Modules\SalesEstimateApproval\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerApprovalTypeMaster\Models\CustomerApprovalTypeMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use App\Modules\SalesEstimateApproval\Models\SalesEstimateApproval;
use App\Modules\SalesEstimateApproval\Models\SalesEstimateApprovalItem;
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
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Sales Estimate Approval')]
class Edit extends Component
{
    use SearchesPickerOptions;

    public ?int $editingId = null;

    public ?string $approval_no = null;

    public ?int $job_card_id = null;

    public ?int $sales_estimate_id = null;

    public ?int $surveyor_inspection_id = null;

    public ?int $insurance_company_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $workshop_department_id = null;

    public ?int $service_type_id = null;

    public ?int $employee_id = null;

    public ?int $approval_mode_id = null;

    public ?int $follow_up_mode_id = null;

    public ?string $approval_type = 'regular';

    public ?string $approval_authorisation = null;

    public ?string $parts_brand_preference = null;

    public ?string $rejection_reason = null;

    public string $status = SalesEstimateApproval::STATUS_SENT;

    public ?string $reminder_frequency = null;

    public ?int $reminder_custom_days = null;

    public ?string $customer_approved_at = null;

    public ?string $insurance_approved_at = null;

    public ?string $notes = null;

    public string $jobCardSearch = '';

    public string $estimateSearch = '';

    public string $vehicleSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public function mount(?SalesEstimateApproval $salesEstimateApproval = null): void
    {
        if ($salesEstimateApproval && $salesEstimateApproval->exists) {
            $this->load($salesEstimateApproval);
        }
    }

    protected function load(SalesEstimateApproval $a): void
    {
        $a->load('items');
        $this->editingId = $a->id;
        foreach ([
            'approval_no', 'job_card_id', 'sales_estimate_id', 'surveyor_inspection_id', 'insurance_company_id',
            'customer_id', 'customer_vehicle_id', 'workshop_department_id', 'service_type_id', 'employee_id',
            'approval_mode_id', 'follow_up_mode_id', 'approval_type', 'approval_authorisation',
            'parts_brand_preference', 'rejection_reason', 'status', 'reminder_frequency', 'reminder_custom_days', 'notes',
        ] as $k) {
            $this->{$k} = $a->{$k};
        }
        $this->customer_approved_at = $a->customer_approved_at?->format('Y-m-d');
        $this->insurance_approved_at = $a->insurance_approved_at?->format('Y-m-d');

        $this->items = $a->items->map(fn (SalesEstimateApprovalItem $i) => [
            'id' => $i->id,
            'line_type' => $i->line_type,
            'spare_id' => $i->spare_id,
            'labour_id' => $i->labour_id,
            'service_package_id' => $i->service_package_id,
            'inventory_group_id' => $i->inventory_group_id,
            'hsn_id' => $i->hsn_id,
            'tax_id' => $i->tax_id,
            'description' => $i->description,
            'quantity' => $i->quantity,
            'unit_rate' => $i->unit_rate,
            'line_approval' => $i->line_approval,
            'depreciation_category' => $i->depreciation_category,
            'depreciation_percent' => $i->depreciation_percent,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'sales_estimate_id' => ['nullable', 'integer', Rule::exists('sales_estimates', 'id')],
            'surveyor_inspection_id' => ['nullable', 'integer', Rule::exists('surveyor_inspections', 'id')],
            'insurance_company_id' => ['nullable', 'integer', Rule::exists('insurance_companies', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'approval_mode_id' => ['nullable', 'integer', Rule::exists('customer_approval_types', 'id')],
            'follow_up_mode_id' => ['nullable', 'integer', Rule::exists('follow_up_modes', 'id')],
            'approval_type' => ['required', Rule::in(array_keys(SalesEstimateApproval::approvalTypes()))],
            'approval_authorisation' => ['nullable', Rule::in(array_keys(SalesEstimateApproval::approvalAuthorisations()))],
            'parts_brand_preference' => ['nullable', Rule::in(array_keys(SalesEstimateApproval::partsBrandPreferences()))],
            'rejection_reason' => ['nullable', Rule::in(array_keys(SalesEstimateApproval::rejectionReasons())), Rule::requiredIf(fn () => $this->status === SalesEstimateApproval::STATUS_REJECTED)],
            'status' => ['required', Rule::in(array_keys(SalesEstimateApproval::statuses()))],
            'reminder_frequency' => ['nullable', Rule::in(array_keys(SalesEstimateApproval::reminderFrequencies()))],
            'reminder_custom_days' => ['nullable', 'integer', 'min:1', 'max:90', Rule::requiredIf(fn () => $this->reminder_frequency === 'custom')],
            'customer_approved_at' => ['nullable', 'date'],
            'insurance_approved_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array'],
            'items.*.line_type' => ['required', 'in:spare,labour,package'],
            'items.*.spare_id' => ['nullable', 'integer', 'exists:spares,id', 'required_if:items.*.line_type,spare'],
            'items.*.labour_id' => ['nullable', 'integer', 'exists:labours,id', 'required_if:items.*.line_type,labour'],
            'items.*.service_package_id' => ['nullable', 'integer', 'exists:service_packages,id', 'required_if:items.*.line_type,package'],
            'items.*.inventory_group_id' => ['nullable', 'integer', 'exists:inventory_groups,id'],
            'items.*.hsn_id' => ['nullable', 'integer', 'exists:hsn_codes,id'],
            'items.*.tax_id' => ['nullable', 'integer', 'exists:taxes,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_approval' => ['nullable', Rule::in(array_keys(SalesEstimateApprovalItem::lineApprovals()))],
            'items.*.depreciation_category' => ['nullable', Rule::in(array_keys(SalesEstimateApprovalItem::depreciationCategories()))],
            'items.*.depreciation_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function addItem(string $type): void
    {
        $this->items[] = [
            'id' => null, 'line_type' => $type, 'spare_id' => null, 'labour_id' => null,
            'service_package_id' => null, 'inventory_group_id' => null, 'hsn_id' => null, 'tax_id' => null,
            'description' => '', 'quantity' => 1, 'unit_rate' => null, 'line_approval' => 'pending',
            'depreciation_category' => null, 'depreciation_percent' => null,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    #[Computed]
    public function companies()
    {
        return InsuranceCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name']);
    }

    #[Computed]
    public function approvalModes()
    {
        return CustomerApprovalTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function followUpModes()
    {
        return FollowUpModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
    public function packages()
    {
        return ServicePackageMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function inventoryGroups()
    {
        return InventoryGroupMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function taxes()
    {
        return TaxMaster::query()->where('is_active', true)->orderBy('gst_percent')->get(['id', 'name', 'gst_percent']);
    }

    #[Computed]
    public function hsnCodes()
    {
        return HsnMaster::query()->where('is_active', true)->orderBy('code')->limit(500)->get(['id', 'code']);
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

    #[Computed]
    public function estimates()
    {
        return $this->pickerOptions(
            query: SalesEstimate::query()->latest('id'),
            searchColumns: ['estimate_no'],
            term: $this->estimateSearch,
            selected: $this->sales_estimate_id,
            columns: ['id', 'estimate_no'],
            limit: 30,
        );
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'sales_estimate_approval.update' : 'sales_estimate_approval.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        unset($data['items']);

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }
        if ($data['reminder_frequency'] !== 'custom') {
            $data['reminder_custom_days'] = null;
        }

        // Stamp the fully-approved timestamp (drives the "today approved" KPI).
        $existing = $this->editingId ? SalesEstimateApproval::find($this->editingId) : null;
        if ($data['status'] === SalesEstimateApproval::STATUS_FULLY_APPROVED && (! $existing || ! $existing->approved_at)) {
            $data['approved_at'] = now();
        }
        if ($data['status'] !== SalesEstimateApproval::STATUS_FULLY_APPROVED) {
            $data['approved_at'] = null;
        }

        $isCreate = $this->editingId === null;

        $approval = DB::transaction(function () use ($data, $items, $isCreate) {
            if ($isCreate) {
                $row = SalesEstimateApproval::create($data);
                $this->editingId = $row->id;
                $this->approval_no = $row->fresh()->approval_no;
            } else {
                $row = SalesEstimateApproval::findOrFail($this->editingId);
                $row->update($data);
            }

            $keptIds = [];
            foreach (array_values($items) as $i => $row2) {
                $keptIds[] = $row->items()->updateOrCreate(
                    ['id' => $row2['id'] ?? null],
                    [
                        'line_type' => $row2['line_type'],
                        'spare_id' => $row2['line_type'] === 'spare' ? ($row2['spare_id'] ?? null) : null,
                        'labour_id' => $row2['line_type'] === 'labour' ? ($row2['labour_id'] ?? null) : null,
                        'service_package_id' => $row2['line_type'] === 'package' ? ($row2['service_package_id'] ?? null) : null,
                        'inventory_group_id' => $row2['inventory_group_id'] ?: null,
                        'hsn_id' => $row2['hsn_id'] ?: null,
                        'tax_id' => $row2['tax_id'] ?: null,
                        'description' => strtoupper(trim((string) $row2['description'])),
                        'quantity' => $row2['quantity'],
                        'unit_rate' => ($row2['unit_rate'] ?? '') !== '' ? $row2['unit_rate'] : null,
                        'line_approval' => $row2['line_approval'] ?: null,
                        'depreciation_category' => $row2['depreciation_category'] ?: null,
                        'depreciation_percent' => ($row2['depreciation_percent'] ?? '') !== '' ? $row2['depreciation_percent'] : null,
                        'sequence_no' => $i + 1,
                    ],
                )->id;
            }
            $row->items()->whereKeyNot($keptIds)->delete();

            return $row;
        });

        Flux::toast(text: 'Estimate approval '.$approval->fresh()->approval_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('sales-estimate-approval.index');
    }

    public function render()
    {
        return view('sales-estimate-approval::edit');
    }
}
