<?php

namespace App\Modules\InvoiceCorrection\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\InvoiceCorrection\Models\InvoiceCorrection;
use App\Modules\InvoiceCorrection\Models\InvoiceCorrectionAttachment;
use App\Modules\InvoiceCorrection\Models\InvoiceCorrectionItem;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
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
#[Title('Invoice Correction')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $correction_no = null;

    public ?string $correction_request_type = null;

    public ?string $correction_reason = null;

    public string $priority = 'normal';

    public ?string $billing_action = null;

    public ?string $invoice_type = null;

    public ?string $invoice_reference = null;

    public string $status = InvoiceCorrection::STATUS_REQUESTED;

    public ?string $rejection_reason = null;

    public ?int $job_card_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $insurance_company_id = null;

    public ?int $workshop_department_id = null;

    public ?int $service_type_id = null;

    public ?int $advisor_id = null;

    public ?int $mistake_by_id = null;

    public ?string $notes = null;

    public string $jobCardSearch = '';

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?InvoiceCorrection $invoiceCorrection = null): void
    {
        if ($invoiceCorrection && $invoiceCorrection->exists) {
            $this->load($invoiceCorrection);

            return;
        }

        $this->items = [$this->blankItem()];
    }

    protected function load(InvoiceCorrection $c): void
    {
        $c->load(['items', 'attachments']);
        $this->editingId = $c->id;
        foreach ([
            'correction_no', 'correction_request_type', 'correction_reason', 'priority', 'billing_action',
            'invoice_type', 'invoice_reference', 'status', 'rejection_reason', 'job_card_id', 'customer_id',
            'customer_vehicle_id', 'insurance_company_id', 'workshop_department_id', 'service_type_id',
            'advisor_id', 'mistake_by_id', 'notes',
        ] as $k) {
            $this->{$k} = $c->{$k};
        }

        $this->items = $c->items->map(fn (InvoiceCorrectionItem $i) => [
            'id' => $i->id,
            'spare_id' => $i->spare_id,
            'uom_id' => $i->uom_id,
            'hsn_id' => $i->hsn_id,
            'tax_id' => $i->tax_id,
            'item_type' => $i->item_type,
            'description' => $i->description,
            'quantity' => $i->quantity,
            'rate' => $i->rate,
            'old_value' => $i->old_value,
            'new_value' => $i->new_value,
            'other_note' => $i->other_note,
            'spareSearch' => '',
        ])->all();

        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $this->attachments = $c->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    /** @return array<string, mixed> */
    protected function blankItem(): array
    {
        return [
            'id' => null, 'spare_id' => null, 'uom_id' => null, 'hsn_id' => null, 'tax_id' => null,
            'item_type' => 'spare', 'description' => '', 'quantity' => null, 'rate' => null,
            'old_value' => null, 'new_value' => null, 'other_note' => null, 'spareSearch' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'correction_request_type' => ['nullable', Rule::in(array_keys(InvoiceCorrection::requestTypes()))],
            'correction_reason' => ['nullable', Rule::in(array_keys(InvoiceCorrection::correctionReasons()))],
            'priority' => ['required', Rule::in(array_keys(InvoiceCorrection::priorities()))],
            'billing_action' => ['nullable', Rule::in(array_keys(InvoiceCorrection::billingActions()))],
            'invoice_type' => ['nullable', Rule::in(array_keys(InvoiceCorrection::invoiceTypes()))],
            'invoice_reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(InvoiceCorrection::statuses()))],
            'rejection_reason' => ['nullable', Rule::in(array_keys(InvoiceCorrection::rejectionReasons())), Rule::requiredIf(fn () => $this->status === InvoiceCorrection::STATUS_REJECTED)],
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'insurance_company_id' => ['nullable', 'integer', Rule::exists('insurance_companies', 'id')],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')],
            'advisor_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'mistake_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array'],
            'items.*.spare_id' => ['nullable', 'integer', Rule::exists('spares', 'id')],
            'items.*.uom_id' => ['nullable', 'integer', Rule::exists('units_of_measure', 'id')],
            'items.*.hsn_id' => ['nullable', 'integer', Rule::exists('hsn_codes', 'id')],
            'items.*.tax_id' => ['nullable', 'integer', Rule::exists('taxes', 'id')],
            'items.*.item_type' => ['required', Rule::in(array_keys(InvoiceCorrectionItem::itemTypes()))],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.old_value' => ['nullable', 'string', 'max:255'],
            'items.*.new_value' => ['nullable', 'string', 'max:255'],
            'items.*.other_note' => ['nullable', 'string', 'max:255'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(InvoiceCorrectionAttachment::attachmentTypes()))],
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
    public function insuranceCompanies()
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

    #[Computed]
    public function jobCards()
    {
        return $this->pickerOptions(
            query: JobCard::query()->latest('id'),
            searchColumns: ['job_card_no'], term: $this->jobCardSearch, selected: $this->job_card_id, columns: ['id', 'job_card_no'], limit: 30,
        );
    }

    #[Computed]
    public function customers()
    {
        return $this->pickerOptions(
            query: CustomerMaster::query()->where('is_active', true)->orderBy('first_name'),
            searchColumns: ['first_name', 'last_name', 'phone'], term: $this->customerSearch, selected: $this->customer_id, columns: ['id', 'first_name', 'last_name'], limit: 30,
        );
    }

    #[Computed]
    public function vehicles()
    {
        return $this->pickerOptions(
            query: CustomerVehicleMaster::query()->orderBy('registration_no'),
            searchColumns: ['registration_no'], term: $this->vehicleSearch, selected: $this->customer_vehicle_id, columns: ['id', 'registration_no'], limit: 30,
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
        $this->authorize($this->editingId ? 'invoice_correction.update' : 'invoice_correction.create');

        $this->items = array_values(array_filter($this->items, fn ($i) => filled($i['description'] ?? null) || filled($i['spare_id'] ?? null) || filled($i['old_value'] ?? null) || filled($i['new_value'] ?? null)));

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['items'], $data['attachments'], $data['attachmentFiles']);

        foreach (['invoice_reference', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $correction = DB::transaction(function () use ($data, $items, $attachments, $isCreate) {
            if ($isCreate) {
                $data['requested_at'] = now();
                if (in_array($data['status'], [InvoiceCorrection::STATUS_APPROVED, InvoiceCorrection::STATUS_CORRECTED], true)) {
                    $data['approved_at'] = now();
                }
                if ($data['status'] === InvoiceCorrection::STATUS_CORRECTED) {
                    $data['corrected_at'] = now();
                }
                $row = InvoiceCorrection::create($data);
                $this->editingId = $row->id;
                $this->correction_no = $row->fresh()->correction_no;
            } else {
                $row = InvoiceCorrection::findOrFail($this->editingId);
                if (in_array($data['status'], [InvoiceCorrection::STATUS_APPROVED, InvoiceCorrection::STATUS_CORRECTED], true) && $row->approved_at === null) {
                    $data['approved_at'] = now();
                }
                if ($data['status'] === InvoiceCorrection::STATUS_CORRECTED && $row->corrected_at === null) {
                    $data['corrected_at'] = now();
                }
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Invoice correction '.$correction->fresh()->correction_no.($isCreate ? ' raised.' : ' updated.'), variant: 'success');

        return redirect()->route('invoice-correction.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncItems(InvoiceCorrection $correction, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = $correction->items()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'spare_id' => $row['spare_id'] ?: null,
                    'uom_id' => $row['uom_id'] ?: null,
                    'hsn_id' => $row['hsn_id'] ?: null,
                    'tax_id' => $row['tax_id'] ?: null,
                    'item_type' => $row['item_type'] ?: 'spare',
                    'description' => strtoupper(trim((string) $row['description'])),
                    'quantity' => $row['quantity'] !== '' ? $row['quantity'] : null,
                    'rate' => $row['rate'] !== '' ? $row['rate'] : null,
                    'old_value' => $row['old_value'] ? strtoupper($row['old_value']) : null,
                    'new_value' => $row['new_value'] ? strtoupper($row['new_value']) : null,
                    'other_note' => isset($row['other_note']) && is_string($row['other_note']) ? strtoupper($row['other_note']) : null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $correction->items()->whereKeyNot($keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(InvoiceCorrection $correction, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('invoice-corrections/'.$correction->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $correction->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $correction->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('invoice-correction::edit');
    }
}
