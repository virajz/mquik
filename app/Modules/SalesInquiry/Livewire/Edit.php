<?php

namespace App\Modules\SalesInquiry\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\SalesInquiry\Models\SalesInquiry;
use App\Modules\SalesInquiry\Models\SalesInquiryAttachment;
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
#[Title('Sales Inquiries')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $inquiry_no = null;

    public ?string $inquiry_type = null;

    public ?string $inquiry_source = null;

    public string $priority = 'normal';

    public string $status = SalesInquiry::STATUS_PENDING;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $workshop_department_id = null;

    public ?int $assigned_by_id = null;

    public ?int $assigned_to_id = null;

    public ?string $follow_up_attempt = null;

    public ?string $escalation = null;

    public ?string $escalation_reason = null;

    public ?string $lost_reason = null;

    public ?string $inquiry_details = null;

    public ?float $estimated_value = null;

    public ?string $notes = null;

    public string $customerSearch = '';

    public string $vehicleSearch = '';

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?SalesInquiry $salesInquiry = null): void
    {
        if ($salesInquiry && $salesInquiry->exists) {
            $this->load($salesInquiry);
        }
    }

    protected function load(SalesInquiry $s): void
    {
        $s->load('attachments');
        $this->editingId = $s->id;
        foreach ([
            'inquiry_no', 'inquiry_type', 'inquiry_source', 'priority', 'status', 'customer_id',
            'customer_vehicle_id', 'workshop_department_id', 'assigned_by_id', 'assigned_to_id',
            'follow_up_attempt', 'escalation', 'escalation_reason', 'lost_reason', 'inquiry_details', 'notes',
        ] as $k) {
            $this->{$k} = $s->{$k};
        }
        $this->estimated_value = $s->estimated_value === null ? null : (float) $s->estimated_value;

        $this->attachments = $s->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
    }

    protected function rules(): array
    {
        return [
            'inquiry_type' => ['nullable', Rule::in(array_keys(SalesInquiry::inquiryTypes()))],
            'inquiry_source' => ['nullable', Rule::in(array_keys(SalesInquiry::inquirySources()))],
            'priority' => ['required', Rule::in(array_keys(SalesInquiry::priorities()))],
            'status' => ['required', Rule::in(array_keys(SalesInquiry::statuses()))],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'assigned_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'assigned_to_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'follow_up_attempt' => ['nullable', Rule::in(array_keys(SalesInquiry::followUpAttempts()))],
            'escalation' => ['nullable', Rule::in(array_keys(SalesInquiry::escalations()))],
            'escalation_reason' => ['nullable', Rule::in(array_keys(SalesInquiry::escalationReasons())), Rule::requiredIf(fn () => filled($this->escalation))],
            'lost_reason' => ['nullable', Rule::in(array_keys(SalesInquiry::lostReasons())), Rule::requiredIf(fn () => $this->status === SalesInquiry::STATUS_LOST)],
            'inquiry_details' => ['nullable', 'string', 'max:2000'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(SalesInquiryAttachment::attachmentTypes()))],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,mp3,m4a,ogg', 'max:20480'],
        ];
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

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'sales_inquiry.update' : 'sales_inquiry.create');

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);

        foreach (['inquiry_details', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $inquiry = DB::transaction(function () use ($data, $attachments, $isCreate) {
            if ($isCreate) {
                $data['inquiry_at'] = now();
                $data = $this->applyStatusTimestamps($data, null);
                $row = SalesInquiry::create($data);
                $this->editingId = $row->id;
                $this->inquiry_no = $row->fresh()->inquiry_no;
            } else {
                $row = SalesInquiry::findOrFail($this->editingId);
                $data = $this->applyStatusTimestamps($data, $row);
                $row->update($data);
            }

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Inquiry '.$inquiry->fresh()->inquiry_no.($isCreate ? ' registered.' : ' updated.'), variant: 'success');

        return redirect()->route('sales-inquiry.index');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyStatusTimestamps(array $data, ?SalesInquiry $existing): array
    {
        if (filled($data['assigned_to_id'] ?? null) && ($existing?->assigned_at === null)) {
            $data['assigned_at'] = now();
        }
        if (($data['status'] ?? null) === SalesInquiry::STATUS_QUOTATION_SENT && ($existing?->quotation_at === null)) {
            $data['quotation_at'] = now();
        }
        if (($data['status'] ?? null) === SalesInquiry::STATUS_CONVERTED && ($existing?->converted_at === null)) {
            $data['converted_at'] = now();
        }
        if (in_array($data['status'] ?? null, [SalesInquiry::STATUS_LOST, SalesInquiry::STATUS_CANCELLED], true) && ($existing?->closed_at === null)) {
            $data['closed_at'] = now();
        }

        return $data;
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(SalesInquiry $inquiry, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('sales-inquiries/'.$inquiry->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $ext = strtolower((string) $upload->getClientOriginalExtension());
                $kind = match (true) {
                    $ext === 'pdf' => 'pdf',
                    in_array($ext, ['mp3', 'm4a', 'ogg'], true) => 'audio',
                    default => 'image',
                };
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $inquiry->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $inquiry->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('sales-inquiry::edit');
    }
}
