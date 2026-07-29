<?php

namespace App\Modules\ConsumableApproval\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\ConsumableApproval\Models\ConsumableApproval;
use App\Modules\ConsumableApproval\Models\ConsumableApprovalAttachment;
use App\Modules\ConsumableApproval\Models\ConsumableApprovalItem;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\OutsideLabourOrder\Models\OutsideLabourOrder;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrder;
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
#[Title('Consumable Approval')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $request_no = null;

    public ?int $job_card_id = null;

    public ?int $workshop_department_id = null;

    public ?int $approval_authority_id = null;

    public ?int $follow_up_mode_id = null;

    public ?string $consumable_category = null;

    public string $priority = 'normal';

    public string $status = ConsumableApproval::STATUS_REQUESTED;

    public ?string $approval_response = null;

    public ?string $notes = null;

    public string $jobCardSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** Fresh per-item damaged-photo uploads keyed by item index. */
    public array $photoFiles = [];

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?ConsumableApproval $consumableApproval = null): void
    {
        if ($consumableApproval && $consumableApproval->exists) {
            $this->load($consumableApproval);

            return;
        }

        $this->items = [$this->blankItem()];
    }

    protected function load(ConsumableApproval $c): void
    {
        $c->load(['items', 'attachments']);
        $this->editingId = $c->id;
        foreach ([
            'request_no', 'job_card_id', 'workshop_department_id', 'approval_authority_id', 'follow_up_mode_id',
            'consumable_category', 'priority', 'status', 'approval_response', 'notes',
        ] as $k) {
            $this->{$k} = $c->{$k};
        }

        $this->items = $c->items->map(fn (ConsumableApprovalItem $i) => [
            'id' => $i->id,
            'vendor_purchase_order_id' => $i->vendor_purchase_order_id,
            'outside_labour_order_id' => $i->outside_labour_order_id,
            'spare_id' => $i->spare_id,
            'uom_id' => $i->uom_id,
            'hsn_id' => $i->hsn_id,
            'tax_id' => $i->tax_id,
            'workshop_department_id' => $i->workshop_department_id,
            'item_type' => $i->item_type,
            'description' => $i->description,
            'quantity' => $i->quantity,
            'rate' => $i->rate,
            'loss_damage_type' => $i->loss_damage_type,
            'damaged_photo_path' => $i->damaged_photo_path,
            'notes' => $i->notes,
            'spareSearch' => '',
            'poSearch' => '',
            'olSearch' => '',
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
            'id' => null, 'vendor_purchase_order_id' => null, 'outside_labour_order_id' => null, 'spare_id' => null,
            'uom_id' => null, 'hsn_id' => null, 'tax_id' => null, 'workshop_department_id' => null,
            'item_type' => 'spare', 'description' => '', 'quantity' => 1, 'rate' => null,
            'loss_damage_type' => null, 'damaged_photo_path' => null, 'notes' => null,
            'spareSearch' => '', 'poSearch' => '', 'olSearch' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'job_card_id' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'approval_authority_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'follow_up_mode_id' => ['nullable', 'integer', Rule::exists('follow_up_modes', 'id')],
            'consumable_category' => ['nullable', Rule::in(array_keys(ConsumableApproval::consumableCategories()))],
            'priority' => ['required', Rule::in(array_keys(ConsumableApproval::priorities()))],
            'status' => ['required', Rule::in(array_keys(ConsumableApproval::statuses()))],
            'approval_response' => ['nullable', Rule::in(array_keys(ConsumableApproval::approvalResponses()))],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array', 'min:1'],
            'items.*.vendor_purchase_order_id' => ['nullable', 'integer', Rule::exists('vendor_purchase_orders', 'id')],
            'items.*.outside_labour_order_id' => ['nullable', 'integer', Rule::exists('outside_labour_orders', 'id')],
            'items.*.spare_id' => ['nullable', 'integer', Rule::exists('spares', 'id')],
            'items.*.uom_id' => ['nullable', 'integer', Rule::exists('units_of_measure', 'id')],
            'items.*.hsn_id' => ['nullable', 'integer', Rule::exists('hsn_codes', 'id')],
            'items.*.tax_id' => ['nullable', 'integer', Rule::exists('taxes', 'id')],
            'items.*.workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')],
            'items.*.item_type' => ['required', Rule::in(array_keys(ConsumableApprovalItem::itemTypes()))],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.loss_damage_type' => ['nullable', Rule::in(array_keys(ConsumableApproval::lossDamageTypes()))],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'photoFiles.*' => ['nullable', 'image', 'max:8192'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(ConsumableApprovalAttachment::attachmentTypes()))],
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
        unset($this->items[$index], $this->photoFiles[$index]);
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
        $this->items[$index]['rate'] = $spare->rate_before_tax;
        if (blank($this->items[$index]['description'])) {
            $this->items[$index]['description'] = $spare->name;
        }
    }

    public function addAttachment(): void
    {
        $this->attachments[] = ['id' => null, 'attachment_type' => 'approval_screenshot', 'path' => null, 'original_name' => null, 'notes' => null];
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
    public function followUpModes()
    {
        return FollowUpModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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

    public function spareOptions(int $index)
    {
        return $this->pickerOptions(
            query: SpareMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'spare_code'], term: $this->items[$index]['spareSearch'] ?? '', selected: $this->items[$index]['spare_id'] ?? null, columns: ['id', 'name', 'spare_code'], limit: 30,
        );
    }

    public function poOptions(int $index)
    {
        return $this->pickerOptions(
            query: VendorPurchaseOrder::query()->latest('id'),
            searchColumns: ['po_no'], term: $this->items[$index]['poSearch'] ?? '', selected: $this->items[$index]['vendor_purchase_order_id'] ?? null, columns: ['id', 'po_no'], limit: 30,
        );
    }

    public function olOptions(int $index)
    {
        return $this->pickerOptions(
            query: OutsideLabourOrder::query()->latest('id'),
            searchColumns: ['order_no'], term: $this->items[$index]['olSearch'] ?? '', selected: $this->items[$index]['outside_labour_order_id'] ?? null, columns: ['id', 'order_no'], limit: 30,
        );
    }

    // ---- Persistence -------------------------------------------------------

    public function save()
    {
        $this->authorize($this->editingId ? 'consumable_approval.update' : 'consumable_approval.create');

        $this->items = array_values(array_filter($this->items, fn ($i) => filled($i['description'] ?? null) || filled($i['spare_id'] ?? null)));
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['items'], $data['attachments'], $data['attachmentFiles'], $data['photoFiles']);

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }

        $isCreate = $this->editingId === null;

        $request = DB::transaction(function () use ($data, $items, $attachments, $isCreate) {
            if ($isCreate) {
                $data['requested_at'] = now();
                if ($data['status'] === ConsumableApproval::STATUS_APPROVED) {
                    $data['approved_at'] = now();
                } elseif ($data['status'] === ConsumableApproval::STATUS_REJECTED) {
                    $data['rejected_at'] = now();
                }
                $row = ConsumableApproval::create($data);
                $this->editingId = $row->id;
                $this->request_no = $row->fresh()->request_no;
            } else {
                $row = ConsumableApproval::findOrFail($this->editingId);
                if ($data['status'] === ConsumableApproval::STATUS_APPROVED && $row->approved_at === null) {
                    $data['approved_at'] = now();
                }
                if ($data['status'] === ConsumableApproval::STATUS_REJECTED && $row->rejected_at === null) {
                    $data['rejected_at'] = now();
                }
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncAttachments($row, $attachments);

            return $row;
        });

        $this->photoFiles = [];
        $this->attachmentFiles = [];

        Flux::toast(text: 'Consumable request '.$request->fresh()->request_no.($isCreate ? ' raised.' : ' updated.'), variant: 'success');

        return redirect()->route('consumable-approval.index');
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncItems(ConsumableApproval $request, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $photoPath = $this->items[$i]['damaged_photo_path'] ?? null;
            $photo = $this->photoFiles[$i] ?? null;
            if ($photo instanceof TemporaryUploadedFile) {
                $photoPath = $photo->store('consumable-approvals/'.$request->id, 'public');
            }

            $keptIds[] = $request->items()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'vendor_purchase_order_id' => $row['vendor_purchase_order_id'] ?: null,
                    'outside_labour_order_id' => $row['outside_labour_order_id'] ?: null,
                    'spare_id' => $row['spare_id'] ?: null,
                    'uom_id' => $row['uom_id'] ?: null,
                    'hsn_id' => $row['hsn_id'] ?: null,
                    'tax_id' => $row['tax_id'] ?: null,
                    'workshop_department_id' => $row['workshop_department_id'] ?: null,
                    'item_type' => $row['item_type'] ?: 'spare',
                    'description' => strtoupper(trim((string) $row['description'])),
                    'quantity' => $row['quantity'],
                    'rate' => $row['rate'] !== '' ? $row['rate'] : null,
                    'loss_damage_type' => $row['loss_damage_type'] ?: null,
                    'damaged_photo_path' => $photoPath,
                    'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $request->items()->whereKeyNot($keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(ConsumableApproval $request, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('consumable-approvals/'.$request->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = $request->attachments()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $request->attachments()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('consumable-approval::edit');
    }
}
