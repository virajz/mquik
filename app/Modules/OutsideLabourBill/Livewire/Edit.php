<?php

namespace App\Modules\OutsideLabourBill\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\OutsideLabourBill\Models\OutsideLabourBill;
use App\Modules\OutsideLabourBill\Models\OutsideLabourBillAttachment;
use App\Modules\OutsideLabourBill\Models\OutsideLabourBillItem;
use App\Modules\OutsideLabourOrder\Models\OutsideLabourOrder;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\ServiceSpecialistMaster\Models\ServiceSpecialistMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
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
#[Title('Outside Labour Bill Verification')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $bill_no = null;

    public ?int $vendor_id = null;

    public ?int $service_specialist_id = null;

    public ?int $outside_labour_order_id = null;

    public ?int $priority_id = null;

    public ?int $requested_by_id = null;

    public ?int $approved_by_id = null;

    public ?int $follow_up_mode_id = null;

    public ?string $bill_document_type = null;

    public ?string $vendor_bill_no = null;

    public ?string $bill_date = null;

    public ?float $bill_amount = null;

    public ?string $work_completion_type = null;

    public string $status = OutsideLabourBill::STATUS_REQUESTED;

    public ?string $hold_reason = null;

    public ?string $rejection_reason = null;

    public ?string $vendor_rating_type = null;

    public ?string $reminder_frequency = null;

    public ?int $reminder_custom_days = null;

    public ?string $notes = null;

    public string $vendorSearch = '';

    public string $orderSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?OutsideLabourBill $outsideLabourBill = null): void
    {
        if ($outsideLabourBill && $outsideLabourBill->exists) {
            $this->load($outsideLabourBill);

            return;
        }

        $this->items = [$this->blankItem()];
    }

    protected function load(OutsideLabourBill $b): void
    {
        $b->load(['items', 'attachments']);
        $this->editingId = $b->id;
        foreach ([
            'bill_no', 'vendor_id', 'service_specialist_id', 'outside_labour_order_id', 'priority_id',
            'requested_by_id', 'approved_by_id', 'follow_up_mode_id', 'bill_document_type', 'vendor_bill_no',
            'work_completion_type', 'status', 'hold_reason', 'rejection_reason', 'vendor_rating_type',
            'reminder_frequency', 'reminder_custom_days', 'notes',
        ] as $k) {
            $this->{$k} = $b->{$k};
        }
        $this->bill_amount = $b->bill_amount === null ? null : (float) $b->bill_amount;
        $this->bill_date = $b->bill_date?->format('Y-m-d');

        $this->items = $b->items->map(fn (OutsideLabourBillItem $i) => [
            'id' => $i->id,
            'job_card_id' => $i->job_card_id,
            'customer_vehicle_id' => $i->customer_vehicle_id,
            'description' => $i->description,
            'quantity' => $i->quantity,
            'rate' => $i->rate,
            'verified_amount' => $i->verified_amount,
            'notes' => $i->notes,
            'jobCardSearch' => '',
            'vehicleSearch' => '',
        ])->all();

        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $this->attachments = $b->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    /** @return array<string, mixed> */
    protected function blankItem(): array
    {
        return [
            'id' => null, 'job_card_id' => null, 'customer_vehicle_id' => null, 'description' => '',
            'quantity' => 1, 'rate' => null, 'verified_amount' => null, 'notes' => null,
            'jobCardSearch' => '', 'vehicleSearch' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'service_specialist_id' => ['nullable', 'integer', Rule::exists('service_specialists', 'id')],
            'outside_labour_order_id' => ['nullable', 'integer', Rule::exists('outside_labour_orders', 'id')],
            'priority_id' => ['nullable', 'integer', Rule::exists('priorities', 'id')],
            'requested_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'approved_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'follow_up_mode_id' => ['nullable', 'integer', Rule::exists('follow_up_modes', 'id')],
            'bill_document_type' => ['nullable', Rule::in(array_keys(OutsideLabourBill::billDocumentTypes()))],
            'vendor_bill_no' => ['nullable', 'string', 'max:80'],
            'bill_date' => ['nullable', 'date'],
            'bill_amount' => ['nullable', 'numeric', 'min:0'],
            'work_completion_type' => ['nullable', Rule::in(array_keys(OutsideLabourBill::workCompletionTypes()))],
            'status' => ['required', Rule::in(array_keys(OutsideLabourBill::statuses()))],
            'hold_reason' => ['nullable', Rule::in(array_keys(OutsideLabourBill::holdReasons())), Rule::requiredIf(fn () => $this->status === OutsideLabourBill::STATUS_ON_HOLD)],
            'rejection_reason' => ['nullable', Rule::in(array_keys(OutsideLabourBill::rejectionReasons())), Rule::requiredIf(fn () => $this->status === OutsideLabourBill::STATUS_REJECTED)],
            'vendor_rating_type' => ['nullable', Rule::in(array_keys(OutsideLabourBill::vendorRatingTypes()))],
            'reminder_frequency' => ['nullable', Rule::in(array_keys(OutsideLabourBill::reminderFrequencies()))],
            'reminder_custom_days' => ['nullable', 'integer', 'min:1', 'max:90', Rule::requiredIf(fn () => $this->reminder_frequency === 'custom')],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array', 'min:1'],
            'items.*.job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'items.*.customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.verified_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(OutsideLabourBillAttachment::attachmentTypes()))],
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
    public function workCategories()
    {
        return ServiceSpecialistMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function priorities()
    {
        return PriorityMaster::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function followUpModes()
    {
        return FollowUpModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
    public function orders()
    {
        return $this->pickerOptions(
            query: OutsideLabourOrder::query()->latest('id'),
            searchColumns: ['order_no'], term: $this->orderSearch, selected: $this->outside_labour_order_id, columns: ['id', 'order_no'], limit: 30,
        );
    }

    public function jobCardOptions(int $index)
    {
        return $this->pickerOptions(
            query: JobCard::query()->latest('id'),
            searchColumns: ['job_card_no'], term: $this->items[$index]['jobCardSearch'] ?? '', selected: $this->items[$index]['job_card_id'] ?? null, columns: ['id', 'job_card_no'], limit: 30,
        );
    }

    public function vehicleOptions(int $index)
    {
        return $this->pickerOptions(
            query: CustomerVehicleMaster::query()->orderBy('registration_no'),
            searchColumns: ['registration_no'], term: $this->items[$index]['vehicleSearch'] ?? '', selected: $this->items[$index]['customer_vehicle_id'] ?? null, columns: ['id', 'registration_no'], limit: 30,
        );
    }

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'outside_labour_bill.update' : 'outside_labour_bill.create');

        $this->items = array_values(array_filter($this->items, fn ($i) => filled($i['description'] ?? null)));
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['items'], $data['attachments'], $data['attachmentFiles']);

        foreach (['vendor_bill_no', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }
        if ($data['reminder_frequency'] !== 'custom') {
            $data['reminder_custom_days'] = null;
        }

        $isCreate = $this->editingId === null;

        $bill = DB::transaction(function () use ($data, $items, $attachments, $isCreate) {
            if ($isCreate) {
                $row = OutsideLabourBill::create($data);
                $this->editingId = $row->id;
                $this->bill_no = $row->fresh()->bill_no;
            } else {
                $row = OutsideLabourBill::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Bill '.$bill->fresh()->bill_no.($isCreate ? ' received.' : ' updated.'), variant: 'success');

        return redirect()->route('outside-labour-bill.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncItems(OutsideLabourBill $bill, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = $bill->items()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'job_card_id' => $row['job_card_id'] ?: null,
                    'customer_vehicle_id' => $row['customer_vehicle_id'] ?: null,
                    'description' => strtoupper(trim((string) $row['description'])),
                    'quantity' => $row['quantity'],
                    'rate' => $row['rate'] !== '' ? $row['rate'] : null,
                    'verified_amount' => $row['verified_amount'] !== '' ? $row['verified_amount'] : null,
                    'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $bill->items()->whereKeyNot($keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(OutsideLabourBill $bill, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('outside-labour-bills/'.$bill->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $bill->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $bill->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('outside-labour-bill::edit');
    }
}
