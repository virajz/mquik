<?php

namespace App\Modules\VendorAdvanceRequest\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\VendorAdvanceRequest\Models\VendorAdvanceRequest;
use App\Modules\VendorAdvanceRequest\Models\VendorAdvanceRequestAttachment;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
use App\Modules\VpoApproval\Models\VpoApproval;
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
#[Title('Vendor Advance Request')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $request_no = null;

    public ?int $job_card_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $workshop_department_id = null;

    public ?int $service_type_id = null;

    public ?int $prepared_by_id = null;

    public ?int $verified_by_id = null;

    public ?int $advisor_id = null;

    public ?int $vendor_id = null;

    public ?int $vendor_purchase_inquiry_id = null;

    public ?int $vpo_approval_id = null;

    public ?int $priority_id = null;

    public ?int $bank_id = null;

    public ?int $follow_up_mode_id = null;

    public ?string $parts_category = null;

    public ?string $advance_reason = null;

    public ?string $payment_mode = null;

    public ?string $vendor_category = null;

    public string $status = VendorAdvanceRequest::STATUS_REQUESTED;

    public ?string $hold_reason = null;

    public ?string $rejection_reason = null;

    public ?string $reminder_frequency = null;

    public ?int $reminder_custom_days = null;

    public ?float $amount = null;

    public ?string $notes = null;

    public string $vendorSearch = '';

    public string $inquirySearch = '';

    public string $jobCardSearch = '';

    /** @var array<int, array{id:?int, document_name:string, is_provided:bool}> */
    public array $documents = [];

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?VendorAdvanceRequest $vendorAdvanceRequest = null): void
    {
        if ($vendorAdvanceRequest && $vendorAdvanceRequest->exists) {
            $this->load($vendorAdvanceRequest);

            return;
        }

        $this->documents = array_map(fn ($n) => ['id' => null, 'document_name' => $n, 'is_provided' => false], VendorAdvanceRequest::standardDocuments());
    }

    protected function load(VendorAdvanceRequest $r): void
    {
        $r->load(['documents', 'attachments']);
        $this->editingId = $r->id;
        foreach ([
            'request_no', 'job_card_id', 'customer_id', 'customer_vehicle_id', 'workshop_department_id',
            'service_type_id', 'prepared_by_id', 'verified_by_id', 'advisor_id', 'vendor_id',
            'vendor_purchase_inquiry_id', 'vpo_approval_id', 'priority_id', 'bank_id', 'follow_up_mode_id',
            'parts_category', 'advance_reason', 'payment_mode', 'vendor_category', 'status', 'hold_reason',
            'rejection_reason', 'reminder_frequency', 'reminder_custom_days', 'notes',
        ] as $k) {
            $this->{$k} = $r->{$k};
        }
        $this->amount = $r->amount === null ? null : (float) $r->amount;

        $this->documents = $r->documents->map(fn ($d) => ['id' => $d->id, 'document_name' => $d->document_name, 'is_provided' => (bool) $d->is_provided])->all();
        $this->attachments = $r->attachments->map(fn ($x) => ['id' => $x->id, 'attachment_type' => $x->attachment_type, 'path' => $x->path, 'original_name' => $x->original_name, 'notes' => $x->notes])->all();
    }

    protected function rules(): array
    {
        return [
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_vehicle_id' => ['nullable', 'integer', Rule::exists('customer_vehicles', 'id')],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')],
            'prepared_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'verified_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'advisor_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'vendor_purchase_inquiry_id' => ['nullable', 'integer', Rule::exists('vendor_purchase_inquiries', 'id')],
            'vpo_approval_id' => ['nullable', 'integer', Rule::exists('vpo_approvals', 'id')],
            'priority_id' => ['nullable', 'integer', Rule::exists('priorities', 'id')],
            'bank_id' => ['nullable', 'integer', Rule::exists('banks', 'id')],
            'follow_up_mode_id' => ['nullable', 'integer', Rule::exists('follow_up_modes', 'id')],
            'parts_category' => ['nullable', Rule::in(array_keys(VendorAdvanceRequest::partsCategories()))],
            'advance_reason' => ['nullable', Rule::in(array_keys(VendorAdvanceRequest::advanceReasons()))],
            'payment_mode' => ['nullable', Rule::in(array_keys(VendorAdvanceRequest::paymentModes()))],
            'vendor_category' => ['nullable', Rule::in(array_keys(VendorAdvanceRequest::vendorCategories()))],
            'status' => ['required', Rule::in(array_keys(VendorAdvanceRequest::statuses()))],
            'hold_reason' => ['nullable', Rule::in(array_keys(VendorAdvanceRequest::holdReasons())), Rule::requiredIf(fn () => $this->status === VendorAdvanceRequest::STATUS_ON_HOLD)],
            'rejection_reason' => ['nullable', Rule::in(array_keys(VendorAdvanceRequest::rejectionReasons())), Rule::requiredIf(fn () => $this->status === VendorAdvanceRequest::STATUS_REJECTED)],
            'reminder_frequency' => ['nullable', Rule::in(array_keys(VendorAdvanceRequest::reminderFrequencies()))],
            'reminder_custom_days' => ['nullable', 'integer', 'min:1', 'max:90', Rule::requiredIf(fn () => $this->reminder_frequency === 'custom')],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'documents' => ['array'],
            'documents.*.document_name' => ['required', 'string', 'max:255'],
            'documents.*.is_provided' => ['boolean'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(VendorAdvanceRequestAttachment::attachmentTypes()))],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ];
    }

    public function addDocument(): void
    {
        $this->documents[] = ['id' => null, 'document_name' => '', 'is_provided' => false];
    }

    public function removeDocument(int $index): void
    {
        unset($this->documents[$index]);
        $this->documents = array_values($this->documents);
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

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function priorities()
    {
        return PriorityMaster::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']);
    }

    #[Computed]
    public function banks()
    {
        return BankMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
    public function inquiries()
    {
        return $this->pickerOptions(
            query: VendorPurchaseInquiry::query()->latest('id'),
            searchColumns: ['vpi_no'], term: $this->inquirySearch, selected: $this->vendor_purchase_inquiry_id, columns: ['id', 'vpi_no'], limit: 30,
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
    public function poApprovals()
    {
        return VpoApproval::query()->latest('id')->limit(100)->get(['id', 'approval_no']);
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'vendor_advance_request.update' : 'vendor_advance_request.create');

        $this->documents = array_values(array_filter($this->documents, fn ($d) => filled($d['document_name'] ?? null)));

        $data = $this->validate();
        $documents = $data['documents'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['documents'], $data['attachments'], $data['attachmentFiles']);

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }
        if ($data['reminder_frequency'] !== 'custom') {
            $data['reminder_custom_days'] = null;
        }

        $isCreate = $this->editingId === null;

        $request = DB::transaction(function () use ($data, $documents, $attachments, $isCreate) {
            if ($isCreate) {
                $row = VendorAdvanceRequest::create($data);
                $this->editingId = $row->id;
                $this->request_no = $row->fresh()->request_no;
            } else {
                $row = VendorAdvanceRequest::findOrFail($this->editingId);
                $row->update($data);
            }

            $keptDocs = [];
            foreach (array_values($documents) as $i => $doc) {
                $keptDocs[] = $row->documents()->updateOrCreate(['id' => $doc['id'] ?? null], [
                    'document_name' => strtoupper(trim((string) $doc['document_name'])),
                    'is_provided' => (bool) ($doc['is_provided'] ?? false),
                    'sequence_no' => $i + 1,
                ])->id;
            }
            $row->documents()->whereKeyNot($keptDocs)->delete();

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Advance request '.$request->fresh()->request_no.($isCreate ? ' raised.' : ' updated.'), variant: 'success');

        return redirect()->route('vendor-advance-request.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(VendorAdvanceRequest $request, array $rows): void
    {
        $keptIds = [];
        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';
            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('vendor-advance-requests/'.$request->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }
            if ($path === null) {
                continue;
            }
            $keptIds[] = $request->attachments()->updateOrCreate(['id' => $row['id'] ?? null], [
                'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
            ])->id;
        }
        $request->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('vendor-advance-request::edit');
    }
}
