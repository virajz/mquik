<?php

namespace App\Modules\DigitalInspection\Livewire;

use App\Modules\DigitalInspection\Models\DigitalInspection;
use App\Modules\DigitalInspection\Support\InspectionStatus;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\StandardObservationMaster\Models\StandardObservationMaster;
use Carbon\CarbonInterface;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Digital Inspection')]
class Edit extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $inspection_no = null;

    public ?int $job_card_id = null;

    public ?int $inspection_template_id = null;

    public ?int $assigned_technician_id = null;

    public ?int $floor_incharge_id = null;

    public string $status = DigitalInspection::STATUS_PENDING;

    public ?string $summary_notes = null;

    /** @var list<array{id: ?int, inspection_item_id: int, inspection_item_group_id: ?int, name: string, group_name: ?string, check_type: string, outcome: string, recommendation: ?string, severity: ?string, observation: ?string, notes: ?string, sequence_no: int, image_path: ?string}> */
    public array $items = [];

    /** @var array<int, TemporaryUploadedFile>  keyed by inspection_item_id — new image uploads not yet persisted */
    public array $itemImages = [];

    /** @var array<int, bool>  keyed by inspection_item_id — true means "remove the saved image for this item on save" */
    public array $itemImageClears = [];

    /** ?int — passed via ?from-job-card=ID query string for the JobCard → DI handoff. */
    #[Url(as: 'from-job-card')]
    public ?int $fromJobCard = null;

    public function mount(?DigitalInspection $digitalInspection = null): void
    {
        if ($digitalInspection && $digitalInspection->exists) {
            $this->load($digitalInspection);

            return;
        }

        if ($this->fromJobCard) {
            $this->job_card_id = $this->fromJobCard;
        }
    }

    protected function load(DigitalInspection $di): void
    {
        $di->load(['items.inspectionItem.group']);

        $this->editingId = $di->id;
        $this->inspection_no = $di->inspection_no;
        $this->job_card_id = $di->job_card_id;
        $this->inspection_template_id = $di->inspection_template_id;
        $this->assigned_technician_id = $di->assigned_technician_id;
        $this->floor_incharge_id = $di->floor_incharge_id;
        $this->status = $di->status;
        $this->summary_notes = $di->summary_notes;

        $this->items = $di->items->map(fn ($i) => [
            'id' => $i->id,
            'inspection_item_id' => $i->inspection_item_id,
            'inspection_item_group_id' => $i->inspection_item_group_id,
            'name' => $i->inspectionItem?->name ?? '—',
            'group_name' => $i->inspectionItem?->group?->name,
            'check_type' => $i->inspectionItem?->check_type ?? 'visual',
            'outcome' => $i->outcome,
            'recommendation' => $i->recommendation,
            'severity' => $i->severity,
            'observation' => $i->observation,
            'notes' => $i->notes,
            'sequence_no' => (int) $i->sequence_no,
            'image_path' => $i->image_path,
        ])->all();
    }

    /**
     * When the template changes (on create), seed every template item into $items
     * with outcome=pending. Lets the technician walk down the list.
     */
    public function updatedInspectionTemplateId(): void
    {
        if ($this->editingId) {
            return;  // never re-seed an existing inspection
        }

        $this->items = [];

        if (! $this->inspection_template_id) {
            return;
        }

        $template = InspectionTemplateMaster::with(['items.group'])->find($this->inspection_template_id);
        if (! $template) {
            return;
        }

        $seq = 1;
        foreach ($template->items as $tplItem) {
            $this->items[] = [
                'id' => null,
                'inspection_item_id' => $tplItem->id,
                'inspection_item_group_id' => $tplItem->inspection_item_group_id,
                'name' => $tplItem->name,
                'group_name' => $tplItem->group?->name,
                'check_type' => $tplItem->check_type,
                'outcome' => 'pending',
                'recommendation' => null,
                'severity' => null,
                'observation' => null,
                'notes' => null,
                'sequence_no' => $seq++,
                'image_path' => null,
            ];
        }
    }

    protected function rules(): array
    {
        return [
            'job_card_id' => ['required', 'integer', 'exists:job_cards,id'],
            'inspection_template_id' => ['required', 'integer', Rule::exists('inspection_templates', 'id')->where('is_active', true)],
            'assigned_technician_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'floor_incharge_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'status' => ['required', Rule::in(array_keys(DigitalInspection::allStatuses()))],
            'summary_notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['array'],
            'items.*.inspection_item_id' => ['required', 'integer', 'exists:inspection_items,id'],
            'items.*.outcome' => ['required', 'string', Rule::in(array_keys(DigitalInspection::allOutcomes()))],
            'items.*.recommendation' => ['nullable', 'string', Rule::in(array_keys(DigitalInspection::allRecommendations()))],
            'items.*.severity' => ['nullable', 'string', Rule::in(array_keys(DigitalInspection::severities()))],
            'items.*.observation' => ['nullable', 'string', 'max:1000'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],

            'itemImages' => ['array'],
            'itemImages.*' => ['image', 'max:8192'],  // 8 MB per item image
        ];
    }

    public function removeItemImage(int $inspectionItemId): void
    {
        // If they had staged a new upload, just discard it.
        unset($this->itemImages[$inspectionItemId]);

        // Mark the saved image for deletion on the next save.
        $this->itemImageClears[$inspectionItemId] = true;

        // Reflect in the local item row so the UI hides the thumbnail immediately.
        foreach ($this->items as $i => $row) {
            if ((int) $row['inspection_item_id'] === $inspectionItemId) {
                $this->items[$i]['image_path'] = null;
                break;
            }
        }
    }

    #[Computed]
    public function jobCards()
    {
        // Only cards still in the workshop: inspecting a closed or cancelled
        // one is never what was meant.
        return JobCard::query()
            ->with(['customerVehicle:id,registration_no,model_id', 'customerVehicle.model:id,name'])
            ->whereIn('status', JobCard::pendingStatuses())
            ->orderByDesc('opened_at')
            ->limit(100)
            ->get(['id', 'job_card_no', 'customer_vehicle_id', 'opened_at']);
    }

    #[Computed]
    public function templates()
    {
        return InspectionTemplateMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'applies_to']);
    }

    /**
     * Staff who actually do this work, by designation. Offering every employee
     * makes the wrong pick as easy as the right one.
     *
     * @return Collection<int, EmployeeMaster>
     */
    protected function staffDesignated(string $needle)
    {
        return EmployeeMaster::query()
            ->where('is_active', true)
            ->whereHas('designation', fn ($q) => $q->whereRaw('upper(name) like ?', ['%'.mb_strtoupper($needle).'%']))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function technicians()
    {
        return $this->staffDesignated('TECHNICIAN');
    }

    /** The master spells it "FLOOR INCHARGE"; match either spelling. */
    #[Computed]
    public function floorIncharges()
    {
        return $this->staffDesignated('FLOOR');
    }

    /**
     * Everything the job card already knows, shown read-only so the inspection
     * never disagrees with the card it belongs to.
     */
    #[Computed]
    public function jobCardContext(): ?JobCard
    {
        if (! $this->job_card_id) {
            return null;
        }

        return JobCard::query()
            ->with([
                'advisor:id,name',
                'workshopDepartment:id,name',
                'serviceType:id,name',
                'customerVehicle:id,registration_no,model_id,variant_id,year_of_manufacture,odometer_km',
                'customerVehicle.model:id,name',
                'customerVehicle.variant:id,name',
            ])
            ->find($this->job_card_id);
    }

    /**
     * Reusable standard observations for the quick-pick datalist on each checklist item.
     */
    #[Computed]
    public function standardObservations()
    {
        return StandardObservationMaster::query()->where('is_active', true)->orderBy('name')->pluck('name');
    }

    /**
     * Technician TAT. The stamps are set by InspectionStatus as the checklist is
     * worked through, so the elapsed figure always matches the sheet.
     */
    #[Computed]
    public function startedAt(): ?CarbonInterface
    {
        return $this->editingId ? DigitalInspection::find($this->editingId)?->started_at : null;
    }

    #[Computed]
    public function completedAt(): ?CarbonInterface
    {
        return $this->editingId ? DigitalInspection::find($this->editingId)?->completed_at : null;
    }

    #[Computed]
    public function assignedTechnicianName(): ?string
    {
        return $this->assigned_technician_id
            ? EmployeeMaster::whereKey($this->assigned_technician_id)->value('name')
            : null;
    }

    /** Elapsed start → finish, or start → now while the sheet is still open. */
    #[Computed]
    public function turnaround(): ?string
    {
        $start = $this->startedAt;

        if (! $start) {
            return null;
        }

        $end = $this->completedAt ?? now();
        $seconds = max(0, $end->getTimestamp() - $start->getTimestamp());

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        $elapsed = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";

        return $this->completedAt ? $elapsed : $elapsed.' (running)';
    }

    #[Computed]
    public function statusExplanation(): string
    {
        return InspectionStatus::explain(
            $this->editingId ? DigitalInspection::find($this->editingId) : null
        );
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'digital_inspection.update' : 'digital_inspection.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        unset($data['items'], $data['itemImages']);

        // Status is derived from the checklist, never typed: keep whatever it is
        // now and let InspectionStatus recompute once the items are written.
        $existing = $this->editingId ? DigitalInspection::find($this->editingId) : null;
        $data['status'] = $existing?->status ?? DigitalInspection::STATUS_PENDING;

        if (isset($data['summary_notes']) && is_string($data['summary_notes'])) {
            $data['summary_notes'] = strtoupper($data['summary_notes']);
        }

        $isCreate = $this->editingId === null;

        $di = DB::transaction(function () use ($data, $items, $isCreate) {
            if ($isCreate) {
                $row = DigitalInspection::create($data);
                $this->editingId = $row->id;
                $this->inspection_no = $row->fresh()->inspection_no;
            } else {
                $row = DigitalInspection::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);

            return $row;
        });

        InspectionStatus::refresh($di);
        $this->status = $di->fresh()->status;

        // Reset transient upload state so a subsequent save on the same component
        // doesn't try to re-process them.
        $this->itemImages = [];
        $this->itemImageClears = [];

        Flux::toast(
            text: 'Inspection '.$di->fresh()->inspection_no.($isCreate ? ' created.' : ' updated.'),
            variant: 'success',
        );

        return redirect()->route('digital-inspection.index');
    }

    /**
     * @param  array<int, array{inspection_item_id: int, outcome: string, notes?: string|null}>  $rows
     */
    protected function syncItems(DigitalInspection $di, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $i => $row) {
            // Pull the rest of the state straight from $this->items so we have group_id + sequence.
            $local = $this->items[$i] ?? null;
            $itemId = (int) $row['inspection_item_id'];

            $imagePath = $local['image_path'] ?? null;
            $existingPath = null;

            // Resolve the existing image_path from DB (it may differ from local if the user has
            // edited the row without reloading).
            if (! empty($local['id'])) {
                $existingPath = $di->items()->whereKey($local['id'])->value('image_path');
            }

            // 1. User-requested clear: delete the old file + null the path.
            if (! empty($this->itemImageClears[$itemId])) {
                if ($existingPath) {
                    Storage::disk('public')->delete($existingPath);
                }
                $imagePath = null;
            }

            // 2. New upload: replace any existing file.
            if (isset($this->itemImages[$itemId]) && $this->itemImages[$itemId] instanceof TemporaryUploadedFile) {
                if ($existingPath) {
                    Storage::disk('public')->delete($existingPath);
                }
                $imagePath = $this->itemImages[$itemId]
                    ->store("digital-inspections/{$di->id}/items", 'public');
            }

            // "No Attention" carries neither: enforced here as well as in the
            // form, so a stale value cannot ride in on a resubmit.
            $isNoAction = $row['outcome'] === DigitalInspection::ACTION_NONE;

            $payload = [
                'inspection_item_id' => $itemId,
                'inspection_item_group_id' => $local['inspection_item_group_id'] ?? null,
                'outcome' => $row['outcome'],
                'recommendation' => $isNoAction ? null : ($local['recommendation'] ?? null),
                'severity' => $isNoAction ? null : ($local['severity'] ?? null),
                'observation' => isset($local['observation']) && is_string($local['observation']) ? strtoupper($local['observation']) : null,
                'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
                'sequence_no' => (int) ($local['sequence_no'] ?? $i + 1),
                'image_path' => $imagePath,
            ];

            if (! empty($local['id'])) {
                $existing = $di->items()->whereKey($local['id'])->first();
                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;
                    $this->items[$i]['image_path'] = $imagePath;

                    continue;
                }
            }

            $created = $di->items()->create($payload);
            $keptIds[] = $created->id;
            $this->items[$i]['id'] = $created->id;
            $this->items[$i]['image_path'] = $imagePath;
        }

        $di->items()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Severity follows the Action Type unless the technician has moved it, and
     * "No Attention" clears both fields — there is nothing to recommend or rate
     * on a checkpoint that needs nothing.
     */
    public function updatedItems($value, string $key): void
    {
        if (! str_ends_with($key, '.outcome')) {
            return;
        }

        $index = (int) explode('.', $key)[0];

        if (! isset($this->items[$index])) {
            return;
        }

        if ($value === DigitalInspection::ACTION_NONE) {
            $this->items[$index]['recommendation'] = null;
            $this->items[$index]['severity'] = null;

            return;
        }

        $suggested = DigitalInspection::severityForAction($value);

        // Only fill a blank, or replace a value this same rule put there — a
        // technician who chose Critical on an IA keeps Critical.
        $current = $this->items[$index]['severity'] ?? null;
        $wasSuggested = in_array($current, ['high', 'low'], true);

        if ($suggested !== null && ($current === null || $current === '' || $wasSuggested)) {
            $this->items[$index]['severity'] = $suggested;
        }
    }

    public function updatedJobCardId(): void
    {
        unset($this->jobCardContext);
        $this->prefillFromJobCard();
    }

    protected function prefillFromJobCard(): void
    {
        if (! $this->job_card_id) {
            return;
        }

        $jobCard = JobCard::find($this->job_card_id);

        if (! $jobCard) {
            return;
        }

        $this->assigned_technician_id = $jobCard->assigned_technician_id;
    }

    public function render()
    {
        return view('digital-inspection::edit');
    }
}
