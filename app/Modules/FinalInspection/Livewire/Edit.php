<?php

namespace App\Modules\FinalInspection\Livewire;

use App\Modules\DigitalInspection\Models\DigitalInspection;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FinalInspection\Models\FinalInspection;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\ReworkReasonMaster\Models\ReworkReasonMaster;
use App\Modules\StandardObservationMaster\Models\StandardObservationMaster;
use Flux\Flux;
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
#[Title('Final Inspection')]
class Edit extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $inspection_no = null;

    public ?int $job_card_id = null;

    public ?int $digital_inspection_id = null;

    public ?int $inspection_template_id = null;

    public ?int $inspector_id = null;

    public ?string $work_completion_type = null;

    public string $status = 'pending';

    public ?int $rework_reason_id = null;

    public ?string $additional_work_recommendation = null;

    public ?string $summary_notes = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    #[Url(as: 'from-job-card')]
    public ?int $fromJobCard = null;

    /** @var list<array<string, mixed>> */
    public array $items = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $itemBeforeFiles = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $itemAfterFiles = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $itemDamageFiles = [];

    /** @var list<array{id: ?int, paused_at: ?string, resumed_at: ?string, notes: ?string}> */
    public array $pauses = [];

    public function mount(?FinalInspection $finalInspection = null): void
    {
        if ($finalInspection && $finalInspection->exists) {
            $this->load($finalInspection);

            return;
        }

        if ($this->fromJobCard) {
            $this->job_card_id = $this->fromJobCard;
        }
    }

    protected function load(FinalInspection $f): void
    {
        $f->load(['items.inspectionItem.group', 'pauses']);

        $this->editingId = $f->id;
        foreach ([
            'inspection_no', 'job_card_id', 'digital_inspection_id', 'inspection_template_id', 'inspector_id',
            'work_completion_type', 'status', 'rework_reason_id', 'additional_work_recommendation', 'summary_notes',
        ] as $k) {
            $this->{$k} = $f->{$k};
        }

        $this->items = $f->items->map(fn ($i) => [
            'id' => $i->id,
            'inspection_item_id' => $i->inspection_item_id,
            'inspection_item_group_id' => $i->inspection_item_group_id,
            'label' => $i->label,
            'group_name' => $i->group?->name,
            'result' => $i->result,
            'recommendation' => $i->recommendation,
            'severity' => $i->severity,
            'observation' => $i->observation,
            'notes' => $i->notes,
            'before_photo_path' => $i->before_photo_path,
            'after_photo_path' => $i->after_photo_path,
            'damage_photo_path' => $i->damage_photo_path,
            'sequence_no' => (int) $i->sequence_no,
        ])->all();

        $this->pauses = $f->pauses->map(fn ($p) => [
            'id' => $p->id,
            'paused_at' => $p->paused_at?->format('Y-m-d\TH:i'),
            'resumed_at' => $p->resumed_at?->format('Y-m-d\TH:i'),
            'notes' => $p->notes,
        ])->all();
    }

    public function updatedInspectionTemplateId(): void
    {
        if ($this->editingId) {
            return;
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
        foreach ($template->items as $tpl) {
            $this->items[] = [
                'id' => null,
                'inspection_item_id' => $tpl->id,
                'inspection_item_group_id' => $tpl->inspection_item_group_id,
                'label' => $tpl->name,
                'group_name' => $tpl->group?->name,
                'result' => 'pending',
                'recommendation' => null,
                'severity' => null,
                'observation' => null,
                'notes' => null,
                'before_photo_path' => null,
                'after_photo_path' => null,
                'damage_photo_path' => null,
                'sequence_no' => $seq++,
            ];
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'id' => null, 'inspection_item_id' => null, 'inspection_item_group_id' => null, 'label' => '', 'group_name' => null,
            'result' => 'pending', 'recommendation' => null, 'severity' => null, 'observation' => null, 'notes' => null,
            'before_photo_path' => null, 'after_photo_path' => null, 'damage_photo_path' => null, 'sequence_no' => count($this->items) + 1,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index], $this->itemBeforeFiles[$index], $this->itemAfterFiles[$index], $this->itemDamageFiles[$index]);
        $this->items = array_values($this->items);
    }

    public function clearItemPhoto(int $index, string $which): void
    {
        $field = $which.'_photo_path';
        if (! empty($this->items[$index][$field])) {
            Storage::disk('public')->delete($this->items[$index][$field]);
        }
        $this->items[$index][$field] = null;
        $prop = 'item'.ucfirst($which).'Files';
        unset($this->{$prop}[$index]);
    }

    public function addPause(): void
    {
        $this->pauses[] = ['id' => null, 'paused_at' => null, 'resumed_at' => null, 'notes' => null];
    }

    public function removePause(int $index): void
    {
        unset($this->pauses[$index]);
        $this->pauses = array_values($this->pauses);
    }

    protected function rules(): array
    {
        return [
            'job_card_id' => ['required', 'integer', 'exists:job_cards,id'],
            'digital_inspection_id' => ['nullable', 'integer', 'exists:digital_inspections,id'],
            'inspection_template_id' => ['nullable', 'integer', Rule::exists('inspection_templates', 'id')->where('is_active', true)],
            'inspector_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'work_completion_type' => ['nullable', Rule::in(array_keys(FinalInspection::completionTypes()))],
            'status' => ['required', Rule::in(array_keys(FinalInspection::statuses()))],
            'rework_reason_id' => ['nullable', 'integer', 'exists:rework_reasons,id'],
            'additional_work_recommendation' => ['nullable', Rule::in(array_keys(FinalInspection::recommendationTypes()))],
            'summary_notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.result' => ['required', Rule::in(array_keys(FinalInspection::results()))],
            'items.*.recommendation' => ['nullable', Rule::in(array_keys(FinalInspection::itemRecommendations()))],
            'items.*.severity' => ['nullable', Rule::in(array_keys(FinalInspection::severities()))],
            'items.*.observation' => ['nullable', 'string', 'max:1000'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],

            'itemBeforeFiles.*' => ['image', 'max:8192'],
            'itemAfterFiles.*' => ['image', 'max:8192'],
            'itemDamageFiles.*' => ['image', 'max:8192'],

            'pauses' => ['array'],
            'pauses.*.paused_at' => ['nullable', 'date'],
            'pauses.*.resumed_at' => ['nullable', 'date'],
            'pauses.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    #[Computed]
    public function jobCards()
    {
        return JobCard::query()->orderByDesc('opened_at')->limit(100)->get(['id', 'job_card_no']);
    }

    #[Computed]
    public function digitalInspections()
    {
        return DigitalInspection::query()
            ->when($this->job_card_id, fn ($q) => $q->where('job_card_id', $this->job_card_id))
            ->orderByDesc('id')->limit(100)->get(['id', 'inspection_no', 'job_card_id']);
    }

    #[Computed]
    public function templates()
    {
        return InspectionTemplateMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'applies_to']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function reworkReasons()
    {
        return ReworkReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function standardObservations()
    {
        return StandardObservationMaster::query()->where('is_active', true)->orderBy('name')->pluck('name');
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'final_inspection.update' : 'final_inspection.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $pauses = $data['pauses'] ?? [];
        unset($data['items'], $data['pauses'], $data['itemBeforeFiles'], $data['itemAfterFiles'], $data['itemDamageFiles']);

        $existing = $this->editingId ? FinalInspection::find($this->editingId) : null;
        if ($data['status'] === 'in_progress' && (! $existing || ! $existing->started_at)) {
            $data['started_at'] = now();
        }
        if ($data['status'] === 'completed') {
            $data['ended_at'] = now();
        }
        if (isset($data['summary_notes']) && is_string($data['summary_notes'])) {
            $data['summary_notes'] = strtoupper($data['summary_notes']);
        }

        $isCreate = $this->editingId === null;

        $fi = DB::transaction(function () use ($data, $items, $pauses, $isCreate) {
            if ($isCreate) {
                $row = FinalInspection::create($data);
                $this->editingId = $row->id;
                $this->inspection_no = $row->fresh()->inspection_no;
            } else {
                $row = FinalInspection::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncPauses($row, $pauses);

            return $row;
        });

        $this->itemBeforeFiles = [];
        $this->itemAfterFiles = [];
        $this->itemDamageFiles = [];

        Flux::toast(text: 'Final inspection '.$fi->fresh()->inspection_no.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        if ($isCreate) {
            return redirect()->route('final-inspection.edit', $fi->id);
        }

        return redirect()->route('final-inspection.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncItems(FinalInspection $fi, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $local = $this->items[$i] ?? [];

            $paths = [];
            foreach (['before', 'after', 'damage'] as $which) {
                $field = $which.'_photo_path';
                $path = $local[$field] ?? null;
                $prop = 'item'.ucfirst($which).'Files';
                if (isset($this->{$prop}[$i]) && $this->{$prop}[$i] instanceof TemporaryUploadedFile) {
                    if ($path) {
                        Storage::disk('public')->delete($path);
                    }
                    $path = $this->{$prop}[$i]->store("final-inspections/{$fi->id}/items", 'public');
                }
                $paths[$field] = $path;
            }

            $payload = [
                'inspection_item_id' => $local['inspection_item_id'] ?? null,
                'inspection_item_group_id' => $local['inspection_item_group_id'] ?? null,
                'label' => strtoupper((string) $row['label']),
                'result' => $row['result'],
                'recommendation' => $local['recommendation'] ?? null,
                'severity' => $local['severity'] ?? null,
                'observation' => isset($local['observation']) && is_string($local['observation']) ? strtoupper($local['observation']) : null,
                'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
                'before_photo_path' => $paths['before_photo_path'],
                'after_photo_path' => $paths['after_photo_path'],
                'damage_photo_path' => $paths['damage_photo_path'],
                'sequence_no' => $i + 1,
            ];

            if (! empty($local['id'])) {
                $ex = $fi->items()->whereKey($local['id'])->first();
                if ($ex) {
                    $ex->update($payload);
                    $keptIds[] = $ex->id;
                    foreach (['before', 'after', 'damage'] as $which) {
                        $this->items[$i][$which.'_photo_path'] = $paths[$which.'_photo_path'];
                    }

                    continue;
                }
            }

            $created = $fi->items()->create($payload);
            $keptIds[] = $created->id;
            $this->items[$i]['id'] = $created->id;
            foreach (['before', 'after', 'damage'] as $which) {
                $this->items[$i][$which.'_photo_path'] = $paths[$which.'_photo_path'];
            }
        }

        $fi->items()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncPauses(FinalInspection $fi, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $local = $this->pauses[$i] ?? [];
            $payload = [
                'paused_at' => $row['paused_at'] ?? null,
                'resumed_at' => $row['resumed_at'] ?? null,
                'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
            ];

            if (! empty($local['id'])) {
                $ex = $fi->pauses()->whereKey($local['id'])->first();
                if ($ex) {
                    $ex->update($payload);
                    $keptIds[] = $ex->id;

                    continue;
                }
            }

            $created = $fi->pauses()->create($payload);
            $keptIds[] = $created->id;
            $this->pauses[$i]['id'] = $created->id;
        }

        $fi->pauses()->whereNotIn('id', $keptIds)->delete();
    }

    public function render()
    {
        return view('final-inspection::edit');
    }
}
