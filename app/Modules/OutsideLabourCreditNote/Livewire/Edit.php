<?php

namespace App\Modules\OutsideLabourCreditNote\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\OutsideLabourBill\Models\OutsideLabourBill;
use App\Modules\OutsideLabourCreditNote\Models\OutsideLabourCreditNote;
use App\Modules\OutsideLabourCreditNote\Models\OutsideLabourCreditNoteAttachment;
use App\Modules\OutsideLabourReturn\Models\OutsideLabourReturn;
use App\Modules\ServiceSpecialistMaster\Models\ServiceSpecialistMaster;
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
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Outside Labour Credit / Debit Note')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $note_no = null;

    public ?string $note_type = 'credit_note';

    public ?string $invoice_type = null;

    public ?int $vendor_id = null;

    public ?int $transport_company_id = null;

    public ?int $service_specialist_id = null;

    public ?int $outside_labour_return_id = null;

    public ?int $job_card_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $workshop_department_id = null;

    public ?int $advisor_id = null;

    public ?string $return_reason = null;

    public ?string $commercial_settlement = null;

    public ?string $warranty_type = null;

    public ?string $warranty_period = null;

    public string $status = OutsideLabourCreditNote::STATUS_POSTED;

    public ?string $vendor_rating_type = null;

    public ?float $amount = null;

    public ?string $notes = null;

    public string $vendorSearch = '';

    public string $transportSearch = '';

    public string $returnSearch = '';

    public string $jobCardSearch = '';

    public string $vehicleSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?OutsideLabourCreditNote $outsideLabourCreditNote = null): void
    {
        if ($outsideLabourCreditNote && $outsideLabourCreditNote->exists) {
            $this->load($outsideLabourCreditNote);

            return;
        }

        $this->items = [$this->blankItem()];
    }

    protected function load(OutsideLabourCreditNote $n): void
    {
        $n->load(['items', 'attachments']);
        $this->editingId = $n->id;
        foreach ([
            'note_no', 'note_type', 'invoice_type', 'vendor_id', 'transport_company_id', 'service_specialist_id',
            'outside_labour_return_id', 'job_card_id', 'customer_vehicle_id', 'workshop_department_id', 'advisor_id',
            'return_reason', 'commercial_settlement', 'warranty_type', 'warranty_period', 'status',
            'vendor_rating_type', 'notes',
        ] as $k) {
            $this->{$k} = $n->{$k};
        }
        $this->amount = $n->amount === null ? null : (float) $n->amount;

        $this->items = $n->items->map(fn (OutsideLabourCreditNoteItem $i) => [
            'id' => $i->id,
            'outside_labour_bill_id' => $i->outside_labour_bill_id,
            'uom_id' => $i->uom_id,
            'hsn_id' => $i->hsn_id,
            'tax_id' => $i->tax_id,
            'description' => $i->description,
            'quantity' => $i->quantity,
            'rate' => $i->rate,
            'notes' => $i->notes,
            'billSearch' => '',
        ])->all();

        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $this->attachments = $n->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    /** @return array<string, mixed> */
    protected function blankItem(): array
    {
        return [
            'id' => null, 'outside_labour_bill_id' => null, 'uom_id' => null, 'hsn_id' => null, 'tax_id' => null,
            'description' => '', 'quantity' => 1, 'rate' => null, 'notes' => null, 'billSearch' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'note_type' => ['required', Rule::in(array_keys(OutsideLabourCreditNote::noteTypes()))],
            'invoice_type' => ['nullable', Rule::in(array_keys(OutsideLabourCreditNote::invoiceTypes()))],
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'transport_company_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')],
            'service_specialist_id' => ['nullable', 'integer', Rule::exists('service_specialists', 'id')],
            'outside_labour_return_id' => ['nullable', 'integer', Rule::exists('outside_labour_returns', 'id')],
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'advisor_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'return_reason' => ['nullable', Rule::in(array_keys(OutsideLabourCreditNote::returnReasons()))],
            'commercial_settlement' => ['nullable', Rule::in(array_keys(OutsideLabourCreditNote::commercialSettlements()))],
            'warranty_type' => ['nullable', Rule::in(array_keys(OutsideLabourCreditNote::warrantyTypes()))],
            'warranty_period' => ['nullable', Rule::in(array_keys(OutsideLabourCreditNote::warrantyPeriods()))],
            'status' => ['required', Rule::in(array_keys(OutsideLabourCreditNote::statuses()))],
            'vendor_rating_type' => ['nullable', Rule::in(array_keys(OutsideLabourCreditNote::vendorRatingTypes()))],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array', 'min:1'],
            'items.*.outside_labour_bill_id' => ['nullable', 'integer', Rule::exists('outside_labour_bills', 'id')],
            'items.*.uom_id' => ['nullable', 'integer', Rule::exists('units_of_measure', 'id')],
            'items.*.hsn_id' => ['nullable', 'integer', Rule::exists('hsn_codes', 'id')],
            'items.*.tax_id' => ['nullable', 'integer', Rule::exists('taxes', 'id')],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(OutsideLabourCreditNoteAttachment::attachmentTypes()))],
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
    public function serviceCategories()
    {
        return ServiceSpecialistMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
    public function vendors()
    {
        return $this->pickerOptions(
            query: VendorMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'vendor_code'], term: $this->vendorSearch, selected: $this->vendor_id, columns: ['id', 'name'], limit: 30,
        );
    }

    #[Computed]
    public function transportCompanies()
    {
        return $this->pickerOptions(
            query: VendorMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'vendor_code'], term: $this->transportSearch, selected: $this->transport_company_id, columns: ['id', 'name'], limit: 30,
        );
    }

    #[Computed]
    public function returns()
    {
        return $this->pickerOptions(
            query: OutsideLabourReturn::query()->latest('id'),
            searchColumns: ['return_no'], term: $this->returnSearch, selected: $this->outside_labour_return_id, columns: ['id', 'return_no'], limit: 30,
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

    #[Computed]
    public function vehicles()
    {
        return $this->pickerOptions(
            query: CustomerVehicleMaster::query()->orderBy('registration_no'),
            searchColumns: ['registration_no'], term: $this->vehicleSearch, selected: $this->customer_vehicle_id, columns: ['id', 'registration_no'], limit: 30,
        );
    }

    public function billOptions(int $index)
    {
        return $this->pickerOptions(
            query: OutsideLabourBill::query()->latest('id'),
            searchColumns: ['bill_no'], term: $this->items[$index]['billSearch'] ?? '', selected: $this->items[$index]['outside_labour_bill_id'] ?? null, columns: ['id', 'bill_no'], limit: 30,
        );
    }

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'outside_labour_credit_note.update' : 'outside_labour_credit_note.create');

        $this->items = array_values(array_filter($this->items, fn ($i) => filled($i['description'] ?? null)));
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['items'], $data['attachments'], $data['attachmentFiles']);

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }

        $isCreate = $this->editingId === null;

        $note = DB::transaction(function () use ($data, $items, $attachments, $isCreate) {
            if ($isCreate) {
                $row = OutsideLabourCreditNote::create($data);
                $this->editingId = $row->id;
                $this->note_no = $row->fresh()->note_no;
            } else {
                $row = OutsideLabourCreditNote::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Note '.$note->fresh()->note_no.($isCreate ? ' posted.' : ' updated.'), variant: 'success');

        return redirect()->route('outside-labour-credit-note.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncItems(OutsideLabourCreditNote $note, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = $note->items()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'outside_labour_bill_id' => $row['outside_labour_bill_id'] ?: null,
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

        $note->items()->whereKeyNot($keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(OutsideLabourCreditNote $note, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('outside-labour-credit-notes/'.$note->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $note->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $note->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('outside-labour-credit-note::edit');
    }
}
