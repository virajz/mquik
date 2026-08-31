<?php

namespace App\Modules\DigitalInspection\Livewire;

use App\Modules\DigitalInspection\Models\DigitalInspection;
use App\Modules\DigitalInspection\Support\InspectionStatus;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\RecommendationCategoryMaster\Models\RecommendationCategoryMaster;
use App\Modules\RecommendationDescriptionMaster\Models\RecommendationDescriptionMaster;
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

    public ?int $advisor_id = null;

    public string $status = DigitalInspection::STATUS_PENDING;

    public ?string $summary_notes = null;

    /** Printed on the customer's copy of the checklist, unlike the internal notes. */
    public ?string $customer_notes = null;

    // ---- Customer explanation & approval (mirrors the physical checklist) ----
    public bool $explained_on_lift = false;

    public bool $media_shared = false;

    public bool $questions_answered = false;

    public ?string $customer_approval = null;

    // ---- Internal control: who signed the sheet off ----
    public ?int $technician_signed_by_id = null;

    public ?int $supervisor_signed_by_id = null;

    public ?int $advisor_signed_by_id = null;

    /**
     * Recommendation descriptions chosen per checklist row, keyed by the row's
     * index: [0 => [3, 7], 1 => []]. Several apply to one checkpoint.
     *
     * @var array<int, list<int>>
     */
    public array $itemRecommendations = [];

    /** Category / sub category narrowing the picker on each row. */
    public array $itemRecCategory = [];

    public array $itemRecSubCategory = [];

    // ---- quick-add a description into the master, category and all ----
    public ?int $quickRecIndex = null;

    public string $quickRecName = '';

    public ?int $quickRecCategoryId = null;

    public ?int $quickRecSubCategoryId = null;

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
        $di->load(['items.inspectionItem.group', 'items.recommendationDescriptions']);

        $this->editingId = $di->id;
        $this->inspection_no = $di->inspection_no;
        $this->job_card_id = $di->job_card_id;
        $this->inspection_template_id = $di->inspection_template_id;
        $this->assigned_technician_id = $di->assigned_technician_id;
        $this->floor_incharge_id = $di->floor_incharge_id;
        $this->advisor_id = $di->advisor_id;
        $this->status = $di->status;
        $this->summary_notes = $di->summary_notes;
        $this->customer_notes = $di->customer_notes;
        $this->explained_on_lift = (bool) $di->explained_on_lift;
        $this->media_shared = (bool) $di->media_shared;
        $this->questions_answered = (bool) $di->questions_answered;
        $this->customer_approval = $di->customer_approval;
        $this->technician_signed_by_id = $di->technician_signed_by_id;
        $this->supervisor_signed_by_id = $di->supervisor_signed_by_id;
        $this->advisor_signed_by_id = $di->advisor_signed_by_id;

        $this->itemRecommendations = $di->items->values()
            ->mapWithKeys(fn ($i, $idx) => [$idx => $i->recommendationDescriptions->modelKeys()])
            ->all();

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
        $this->itemRecommendations = [];
        $this->itemRecCategory = [];
        $this->itemRecSubCategory = [];

        if (! $this->inspection_template_id) {
            return;
        }

        $template = InspectionTemplateMaster::with(['items.group'])->find($this->inspection_template_id);
        if (! $template) {
            return;
        }

        // The sheet is walked in a physical order — bonnet, then wheels, then
        // interior — so the masters' own sequence wins over the template's.
        $ordered = $template->items->sortBy([
            fn ($a, $b) => ($a->group?->sequence_no ?? 0) <=> ($b->group?->sequence_no ?? 0),
            fn ($a, $b) => strcmp((string) $a->group?->name, (string) $b->group?->name),
            fn ($a, $b) => ($a->sequence_no ?? 0) <=> ($b->sequence_no ?? 0),
            fn ($a, $b) => strcmp((string) $a->name, (string) $b->name),
        ]);

        $seq = 1;
        foreach ($ordered as $tplItem) {
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
                'sequence_no' => $seq,
                'image_path' => null,
            ];
            $this->itemRecommendations[$seq - 1] = [];
            $seq++;
        }
    }

    protected function rules(): array
    {
        return [
            'job_card_id' => ['required', 'integer', 'exists:job_cards,id'],
            'inspection_template_id' => ['required', 'integer', Rule::exists('inspection_templates', 'id')->where('is_active', true)],
            // Somebody has to do the work.
            'assigned_technician_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            // And somebody has to answer for it — the floor or the front desk.
            // Either satisfies this; neither does not.
            'floor_incharge_id' => [
                Rule::requiredIf(fn () => ! $this->advisor_id),
                'nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true),
            ],
            'advisor_id' => [
                Rule::requiredIf(fn () => ! $this->floor_incharge_id),
                'nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true),
            ],
            'status' => ['required', Rule::in(array_keys(DigitalInspection::allStatuses()))],
            'summary_notes' => ['nullable', 'string', 'max:2000'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['array'],
            'items.*.inspection_item_id' => ['required', 'integer', 'exists:inspection_items,id'],
            'explained_on_lift' => ['boolean'],
            'media_shared' => ['boolean'],
            'questions_answered' => ['boolean'],
            'customer_approval' => ['nullable', Rule::in(array_keys(DigitalInspection::customerApprovals()))],
            'technician_signed_by_id' => ['nullable', 'integer', 'exists:employees,id'],
            'supervisor_signed_by_id' => ['nullable', 'integer', 'exists:employees,id'],
            'advisor_signed_by_id' => ['nullable', 'integer', 'exists:employees,id'],

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

    /** Advisors sign the third internal-control row. */
    #[Computed]
    public function advisors()
    {
        return $this->staffDesignated('ADVISOR');
    }

    #[Computed]
    public function customerApprovalAt(): ?CarbonInterface
    {
        return $this->editingId ? DigitalInspection::find($this->editingId)?->customer_approval_at : null;
    }

    /** When a given internal-control row was signed, if it has been. */
    public function signedAt(string $role): ?CarbonInterface
    {
        if (! in_array($role, ['technician', 'supervisor', 'advisor'], true) || ! $this->editingId) {
            return null;
        }

        return DigitalInspection::find($this->editingId)?->{$role.'_signed_at'};
    }

    /**
     * Descriptions offered on one checklist row.
     *
     * Narrowed to the row's chosen Category and Sub Category, because a
     * technician looking at brake pads should not scroll past every phrase in
     * the workshop. Whatever is already picked is always included, or a row
     * would render blank for a value it holds.
     *
     * @return Collection<int, RecommendationDescriptionMaster>
     */
    public function recommendationOptions(int $index)
    {
        $chosen = $this->itemRecommendations[$index] ?? [];
        $categoryId = $this->itemRecCategory[$index] ?? null;
        $subCategoryId = $this->itemRecSubCategory[$index] ?? null;

        return RecommendationDescriptionMaster::query()
            ->where(fn ($q) => $q
                ->where(fn ($inner) => $inner
                    ->where('is_active', true)
                    ->when($categoryId, fn ($c) => $c->where('category_id', (int) $categoryId))
                    ->when($subCategoryId, fn ($c) => $c->where('sub_category_id', (int) $subCategoryId)))
                ->orWhereIn('id', $chosen))
            ->orderBy('sequence_no')->orderBy('name')
            ->get(['id', 'name', 'category_id', 'sub_category_id']);
    }

    /** Tick everything currently on offer for a row. */
    public function selectAllRecommendations(int $index): void
    {
        $this->itemRecommendations[$index] = $this->recommendationOptions($index)->modelKeys();
    }

    public function clearRecommendations(int $index): void
    {
        $this->itemRecommendations[$index] = [];
    }

    #[Computed]
    public function recommendationCategories()
    {
        return RecommendationCategoryMaster::query()
            ->categories()->where('is_active', true)
            ->orderBy('sequence_no')->orderBy('name')
            ->get(['id', 'name']);
    }

    /** Sub categories under whichever category a given row has chosen. */
    public function recommendationSubCategories(?int $categoryId)
    {
        if (! $categoryId) {
            return collect();
        }

        return RecommendationCategoryMaster::query()
            ->subCategories($categoryId)->where('is_active', true)
            ->orderBy('sequence_no')->orderBy('name')
            ->get(['id', 'name']);
    }

    /** Picking a different category invalidates the sub category under the old one. */
    public function updatedItemRecCategory($value, string $key): void
    {
        $this->itemRecSubCategory[(int) $key] = null;
    }

    public function openRecommendationQuickAdd(int $index): void
    {
        $this->quickRecIndex = $index;
        $this->quickRecName = '';
        $this->quickRecCategoryId = $this->itemRecCategory[$index] ?? null;
        $this->quickRecSubCategoryId = $this->itemRecSubCategory[$index] ?? null;
        $this->resetErrorBag(['quickRecName', 'quickRecCategoryId', 'quickRecSubCategoryId']);

        Flux::modal('di-recommendation-quick-add')->show();
    }

    public function updatedQuickRecCategoryId(): void
    {
        $this->quickRecSubCategoryId = null;
    }

    /** Add wording to the master mid-inspection and tick it on the row at once. */
    public function createRecommendationDescription(): void
    {
        $this->authorize('recommendation_description_master.create');

        $this->validate([
            'quickRecName' => ['required', 'string', 'max:255'],
            'quickRecCategoryId' => ['required', 'integer',
                Rule::exists('recommendation_categories', 'id')->whereNull('parent_id'),
            ],
            'quickRecSubCategoryId' => ['nullable', 'integer',
                Rule::exists('recommendation_categories', 'id')->where('parent_id', $this->quickRecCategoryId),
            ],
        ], attributes: [
            'quickRecName' => 'description',
            'quickRecCategoryId' => 'category',
            'quickRecSubCategoryId' => 'sub category',
        ]);

        $description = RecommendationDescriptionMaster::firstOrCreate(
            [
                'name' => mb_strtoupper($this->quickRecName),
                'category_id' => $this->quickRecCategoryId,
                'sub_category_id' => $this->quickRecSubCategoryId,
            ],
            ['is_active' => true],
        );

        $index = (int) $this->quickRecIndex;
        $this->itemRecCategory[$index] = $this->quickRecCategoryId;
        $this->itemRecSubCategory[$index] = $this->quickRecSubCategoryId;
        $this->itemRecommendations[$index] = array_values(array_unique(
            array_merge($this->itemRecommendations[$index] ?? [], [$description->id])
        ));

        $this->quickRecIndex = null;
        $this->quickRecName = '';

        Flux::modal('di-recommendation-quick-add')->close();
        Flux::toast(text: 'Recommendation added and ticked.', variant: 'success');
    }

    #[Computed]
    public function statusExplanation(): string
    {
        return InspectionStatus::explain(
            $this->editingId ? DigitalInspection::find($this->editingId) : null
        );
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'floor_incharge_id.required' => 'Name a floor in-charge or an advisor.',
            'advisor_id.required' => 'Name a floor in-charge or an advisor.',
        ];
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

        // A signature is the moment somebody put their name to it, so the stamp
        // is set when the name first appears and cleared when it is taken off.
        foreach (['technician', 'supervisor', 'advisor'] as $role) {
            $who = $data[$role.'_signed_by_id'] ?? null;
            $wasSignedBy = $existing?->{$role.'_signed_by_id'};

            $data[$role.'_signed_at'] = match (true) {
                $who === null => null,
                $who !== $wasSignedBy => now(),
                default => $existing?->{$role.'_signed_at'} ?? now(),
            };
        }

        // Same for the customer's decision.
        $data['customer_approval_at'] = match (true) {
            ($data['customer_approval'] ?? null) === null => null,
            $data['customer_approval'] !== $existing?->customer_approval => now(),
            default => $existing?->customer_approval_at ?? now(),
        };

        // Workshop convention: capital typing on free text, customer copy included.
        foreach (['summary_notes', 'customer_notes'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = strtoupper($data[$field]);
            }
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

            $row = null;

            if (! empty($local['id'])) {
                $row = $di->items()->whereKey($local['id'])->first();
                if ($row) {
                    $row->update($payload);
                }
            }

            if (! $row) {
                $row = $di->items()->create($payload);
                $this->items[$i]['id'] = $row->id;
            }

            $keptIds[] = $row->id;
            $this->items[$i]['image_path'] = $imagePath;

            // "No attention" carries no recommendations either.
            $picked = $isNoAction ? [] : array_values(array_unique($this->itemRecommendations[$i] ?? []));
            $row->recommendationDescriptions()->sync(
                collect($picked)->mapWithKeys(fn ($id, $n) => [(int) $id => ['sequence_no' => $n + 1]])->all()
            );
            $this->itemRecommendations[$i] = $picked;
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
            $this->itemRecommendations[$index] = [];

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
