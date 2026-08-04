<?php

namespace App\Modules\VehicleAmc\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VehicleAmc\Models\VehicleAmc;
use App\Modules\VehicleAmc\Models\VehicleAmcAttachment;
use App\Modules\VehicleAmc\Models\VehicleAmcItem;
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
#[Title('Vehicle AMC')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $amc_no = null;

    public ?int $workshop_department_id = null;

    public ?int $sold_by_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?string $amc_package = null;

    public ?string $amc_validity = null;

    public ?int $services_limit = null;

    public int $services_availed = 0;

    public string $status = VehicleAmc::STATUS_ACTIVE;

    public string $payment_status = 'pending';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public ?float $amount = null;

    public ?string $terms_conditions = null;

    public ?string $notes = null;

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?VehicleAmc $vehicleAmc = null): void
    {
        if ($vehicleAmc && $vehicleAmc->exists) {
            $this->load($vehicleAmc);

            return;
        }

        $this->items = [$this->blankItem()];
    }

    protected function load(VehicleAmc $a): void
    {
        $a->load(['items', 'attachments']);
        $this->editingId = $a->id;
        foreach ([
            'amc_no', 'workshop_department_id', 'sold_by_id', 'customer_id', 'customer_vehicle_id', 'amc_package',
            'amc_validity', 'services_limit', 'services_availed', 'status', 'payment_status', 'terms_conditions', 'notes',
        ] as $k) {
            $this->{$k} = $a->{$k};
        }
        $this->start_date = $a->start_date?->format('Y-m-d');
        $this->end_date = $a->end_date?->format('Y-m-d');
        $this->amount = $a->amount === null ? null : (float) $a->amount;

        $this->items = $a->items->map(fn (VehicleAmcItem $i) => [
            'id' => $i->id, 'spare_id' => $i->spare_id, 'uom_id' => $i->uom_id, 'hsn_id' => $i->hsn_id,
            'tax_id' => $i->tax_id, 'item_type' => $i->item_type, 'description' => $i->description,
            'quantity' => $i->quantity, 'discount_value' => $i->discount_value, 'spareSearch' => '',
        ])->all();

        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $this->attachments = $a->attachments->map(fn ($x) => [
            'id' => $x->id, 'attachment_type' => $x->attachment_type, 'path' => $x->path,
            'original_name' => $x->original_name, 'notes' => $x->notes,
        ])->all();
    }

    /** @return array<string, mixed> */
    protected function blankItem(): array
    {
        return [
            'id' => null, 'spare_id' => null, 'uom_id' => null, 'hsn_id' => null, 'tax_id' => null,
            'item_type' => 'labour', 'description' => '', 'quantity' => 1, 'discount_value' => null, 'spareSearch' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'sold_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'amc_package' => ['nullable', Rule::in(array_keys(VehicleAmc::packages()))],
            'amc_validity' => ['nullable', Rule::in(array_keys(VehicleAmc::validities()))],
            'services_limit' => ['nullable', 'integer', Rule::in(array_keys(VehicleAmc::serviceLimits()))],
            'services_availed' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(array_keys(VehicleAmc::statuses()))],
            'payment_status' => ['required', Rule::in(array_keys(VehicleAmc::paymentStatuses()))],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'terms_conditions' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array'],
            'items.*.spare_id' => ['nullable', 'integer', Rule::exists('spares', 'id')],
            'items.*.uom_id' => ['nullable', 'integer', Rule::exists('units_of_measure', 'id')],
            'items.*.hsn_id' => ['nullable', 'integer', Rule::exists('hsn_codes', 'id')],
            'items.*.tax_id' => ['nullable', 'integer', Rule::exists('taxes', 'id')],
            'items.*.item_type' => ['required', Rule::in(array_keys(VehicleAmcItem::itemTypes()))],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(VehicleAmcAttachment::attachmentTypes()))],
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
        $this->authorize($this->editingId ? 'vehicle_amc.update' : 'vehicle_amc.create');

        $this->items = array_values(array_filter($this->items, fn ($i) => filled($i['description'] ?? null) || filled($i['spare_id'] ?? null)));

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['items'], $data['attachments'], $data['attachmentFiles']);

        foreach (['terms_conditions', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $amc = DB::transaction(function () use ($data, $items, $attachments, $isCreate) {
            if ($isCreate) {
                $row = VehicleAmc::create($data);
                $this->editingId = $row->id;
                $this->amc_no = $row->fresh()->amc_no;
            } else {
                $row = VehicleAmc::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'AMC '.$amc->fresh()->amc_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('vehicle-amc.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncItems(VehicleAmc $amc, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = ChildRows::upsert($amc->items(), $row['id'] ?? null,
                [
                    'spare_id' => $row['spare_id'] ?: null,
                    'uom_id' => $row['uom_id'] ?: null,
                    'hsn_id' => $row['hsn_id'] ?: null,
                    'tax_id' => $row['tax_id'] ?: null,
                    'item_type' => $row['item_type'] ?: 'labour',
                    'description' => strtoupper(trim((string) $row['description'])),
                    'quantity' => $row['quantity'],
                    'discount_value' => $row['discount_value'] !== '' ? $row['discount_value'] : null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $amc->items()->whereKeyNot($keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(VehicleAmc $amc, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('vehicle-amcs/'.$amc->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = ChildRows::upsert($amc->attachments(), $row['id'] ?? null,
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $amc->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('vehicle-amc::edit');
    }
}
