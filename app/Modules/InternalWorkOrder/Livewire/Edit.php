<?php

namespace App\Modules\InternalWorkOrder\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InternalWorkOrder\Models\InternalWorkOrder;
use App\Modules\InternalWorkOrder\Models\InternalWorkOrderAttachment;
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
#[Title('Internal Work Order')]
class Edit extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $iwo_no = null;

    public ?string $iwo_type = null;

    public ?string $iwo_category = null;

    public string $priority = 'normal';

    public ?string $department = null;

    public ?int $requested_by_id = null;

    public ?int $requested_to_id = null;

    public ?int $assigned_to_id = null;

    public string $status = InternalWorkOrder::STATUS_REQUESTED;

    public ?string $iwo_response = null;

    public ?string $follow_up_mode = null;

    public ?string $escalation = null;

    public ?string $root_cause = null;

    public ?string $corrective_action = null;

    public ?string $title = null;

    public ?string $description = null;

    public ?string $notes = null;

    public ?string $due_at = null;

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?InternalWorkOrder $internalWorkOrder = null): void
    {
        if ($internalWorkOrder && $internalWorkOrder->exists) {
            $this->load($internalWorkOrder);
        }
    }

    protected function load(InternalWorkOrder $r): void
    {
        $r->load('attachments');
        $this->editingId = $r->id;
        foreach ([
            'iwo_no', 'iwo_type', 'iwo_category', 'priority', 'department', 'requested_by_id', 'requested_to_id',
            'assigned_to_id', 'status', 'iwo_response', 'follow_up_mode', 'escalation', 'root_cause',
            'corrective_action', 'title', 'description', 'notes',
        ] as $k) {
            $this->{$k} = $r->{$k};
        }
        $this->due_at = $r->due_at?->format('Y-m-d');

        $this->attachments = $r->attachments->map(fn ($att) => [
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
            'iwo_type' => ['nullable', Rule::in(array_keys(InternalWorkOrder::types()))],
            'iwo_category' => ['nullable', Rule::in(array_keys(InternalWorkOrder::categories()))],
            'priority' => ['required', Rule::in(array_keys(InternalWorkOrder::priorities()))],
            'department' => ['nullable', Rule::in(array_keys(InternalWorkOrder::departments()))],
            'requested_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'requested_to_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'assigned_to_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'status' => ['required', Rule::in(array_keys(InternalWorkOrder::statuses()))],
            'iwo_response' => ['nullable', Rule::in(array_keys(InternalWorkOrder::responses()))],
            'follow_up_mode' => ['nullable', Rule::in(array_keys(InternalWorkOrder::followUpModes()))],
            'escalation' => ['nullable', Rule::in(array_keys(InternalWorkOrder::escalations()))],
            'root_cause' => ['nullable', Rule::in(array_keys(InternalWorkOrder::rootCauses()))],
            'corrective_action' => ['nullable', Rule::in(array_keys(InternalWorkOrder::correctiveActions())), Rule::requiredIf(fn () => $this->status === InternalWorkOrder::STATUS_RESOLVED)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'due_at' => ['nullable', 'date'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(InternalWorkOrderAttachment::attachmentTypes()))],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,mp4,mov,webm', 'max:20480'],
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

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'internal_work_order.update' : 'internal_work_order.create');

        $data = $this->validate();
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments'], $data['attachmentFiles']);

        foreach (['title', 'description', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $iwo = DB::transaction(function () use ($data, $attachments, $isCreate) {
            $existing = $isCreate ? null : InternalWorkOrder::findOrFail($this->editingId);
            $data = $this->applyTimestamps($data, $existing);

            if ($isCreate) {
                $row = InternalWorkOrder::create($data);
                $this->editingId = $row->id;
                $this->iwo_no = $row->fresh()->iwo_no;
            } else {
                $existing->update($data);
                $row = $existing;
            }

            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->attachmentFiles = [];

        Flux::toast(text: 'IWO '.$iwo->fresh()->iwo_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        return redirect()->route('internal-work-order.index');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyTimestamps(array $data, ?InternalWorkOrder $existing): array
    {
        if ($existing === null && empty($data['complaint_at'])) {
            $data['complaint_at'] = now();
        }

        if (filled($data['assigned_to_id'] ?? null) && ($existing?->assigned_at === null)) {
            $data['assigned_at'] = now();
        }

        $status = $data['status'] ?? null;

        if ($status === InternalWorkOrder::STATUS_IN_PROGRESS && ($existing?->work_started_at === null)) {
            $data['work_started_at'] = now();
        }
        if ($status === InternalWorkOrder::STATUS_RESOLVED && ($existing?->resolved_at === null)) {
            $data['resolved_at'] = now();
        }
        if ($status === InternalWorkOrder::STATUS_CANCELLED && ($existing?->cancelled_at === null)) {
            $data['cancelled_at'] = now();
        }

        return $data;
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(InternalWorkOrder $iwo, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('internal-work-orders/'.$iwo->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $ext = strtolower((string) $upload->getClientOriginalExtension());
                $kind = match (true) {
                    $ext === 'pdf' => 'pdf',
                    in_array($ext, ['mp4', 'mov', 'webm'], true) => 'video',
                    default => 'image',
                };
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $iwo->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $iwo->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('internal-work-order::edit');
    }
}
