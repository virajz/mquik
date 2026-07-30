<?php

namespace App\Modules\StockMismatchApproval\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\StockCounting\Models\StockCount;
use App\Modules\StockMismatchApproval\Models\StockMismatchApproval;
use App\Modules\StockMismatchApproval\Models\StockMismatchApprovalAttachment;
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
#[Title('Stock Mismatch Approval')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $approval_no = null;

    public ?int $stock_count_id = null;

    public ?int $requested_by_id = null;

    public ?int $requested_to_id = null;

    public string $approval_status = StockMismatchApproval::STATUS_REQUESTED;

    public ?string $variance_reason = null;

    public ?string $management_response = null;

    public ?string $recount_outcome = null;

    public ?string $adjustment_method = null;

    public ?string $communication_mode = null;

    public ?string $notes = null;

    public string $countSearch = '';

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?StockMismatchApproval $stockMismatchApproval = null): void
    {
        if ($stockMismatchApproval && $stockMismatchApproval->exists) {
            $this->load($stockMismatchApproval);
        }
    }

    protected function load(StockMismatchApproval $a): void
    {
        $a->load('attachments');
        $this->editingId = $a->id;
        foreach ([
            'approval_no', 'stock_count_id', 'requested_by_id', 'requested_to_id', 'approval_status',
            'variance_reason', 'management_response', 'recount_outcome', 'adjustment_method', 'communication_mode', 'notes',
        ] as $k) {
            $this->{$k} = $a->{$k};
        }

        $this->attachments = $a->attachments->map(fn ($att) => [
            'id' => $att->id, 'attachment_type' => $att->attachment_type, 'path' => $att->path,
            'original_name' => $att->original_name, 'notes' => $att->notes,
        ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'stock_count_id' => ['nullable', 'integer', Rule::exists('stock_counts', 'id')],
            'requested_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'requested_to_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'approval_status' => ['required', Rule::in(array_keys(StockMismatchApproval::approvalStatuses()))],
            'variance_reason' => ['nullable', Rule::in(array_keys(StockMismatchApproval::varianceReasons()))],
            'management_response' => ['nullable', Rule::in(array_keys(StockMismatchApproval::managementResponses())), Rule::requiredIf(fn () => $this->approval_status === StockMismatchApproval::STATUS_APPROVED)],
            'recount_outcome' => ['nullable', Rule::in(array_keys(StockMismatchApproval::recountOutcomes()))],
            'adjustment_method' => ['nullable', Rule::in(array_keys(StockMismatchApproval::adjustmentMethods()))],
            'communication_mode' => ['nullable', Rule::in(array_keys(StockMismatchApproval::communicationModes()))],
            'notes' => ['nullable', 'string', 'max:2000'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(StockMismatchApprovalAttachment::attachmentTypes()))],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
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
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function stockCounts()
    {
        return $this->pickerOptions(
            query: StockCount::query()->latest('id'),
            searchColumns: ['count_no'], term: $this->countSearch, selected: $this->stock_count_id, columns: ['id', 'count_no'], limit: 30,
        );
    }

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'stock_mismatch_approval.update' : 'stock_mismatch_approval.create');

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }

        $isCreate = $this->editingId === null;

        $approval = DB::transaction(function () use ($data, $attachments, $isCreate) {
            $existing = $isCreate ? null : StockMismatchApproval::findOrFail($this->editingId);
            $data = $this->applyTimestamps($data, $existing);

            if ($isCreate) {
                $row = StockMismatchApproval::create($data);
                $this->editingId = $row->id;
                $this->approval_no = $row->fresh()->approval_no;
            } else {
                $existing->update($data);
                $row = $existing;
            }

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'Approval '.$approval->fresh()->approval_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('stock-mismatch-approval.index');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyTimestamps(array $data, ?StockMismatchApproval $existing): array
    {
        if ($existing === null && empty($data['requested_at'])) {
            $data['requested_at'] = now();
        }

        $status = $data['approval_status'] ?? null;

        if ($status === StockMismatchApproval::STATUS_APPROVED && ($existing?->approved_at === null)) {
            $data['approved_at'] = now();
        }
        if ($status === StockMismatchApproval::STATUS_REJECTED && ($existing?->rejected_at === null)) {
            $data['rejected_at'] = now();
        }
        if ($status === StockMismatchApproval::STATUS_CANCELLED && ($existing?->cancelled_at === null)) {
            $data['cancelled_at'] = now();
        }

        return $data;
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(StockMismatchApproval $approval, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('stock-mismatch-approvals/'.$approval->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $approval->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $approval->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('stock-mismatch-approval::edit');
    }
}
