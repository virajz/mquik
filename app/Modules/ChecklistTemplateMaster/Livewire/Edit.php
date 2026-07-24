<?php

namespace App\Modules\ChecklistTemplateMaster\Livewire;

use App\Concerns\HasQuickCreate;
use App\Modules\ChecklistGroupMaster\Models\ChecklistGroupMaster;
use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Checklist Template')]
class Edit extends Component
{
    use HasQuickCreate;

    public ?int $editingId = null;

    public string $checklistGroupSearch = '';

    public string $name = '';

    public ?string $code = null;

    public ?int $checklist_group_id = null;

    public string $applies_to = 'generic';

    /** @var array<int, array{label: string, is_required: bool}> */
    public array $items = [];

    public bool $is_active = true;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('checklist_templates', 'name')
                    ->where(fn ($q) => $q->where('checklist_group_id', $this->checklist_group_id))
                    ->ignore($this->editingId),
            ],
            'code' => [
                'nullable', 'string', 'max:30',
                Rule::unique('checklist_templates', 'code')->ignore($this->editingId),
            ],
            'checklist_group_id' => ['required', 'integer', Rule::exists('checklist_groups', 'id')],
            'applies_to' => ['required', Rule::in(ChecklistTemplateMaster::appliesToOptions())],
            'items' => ['required', 'array', 'min:1'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.is_required' => ['boolean'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function mount(?ChecklistTemplateMaster $checklistTemplateMaster = null): void
    {
        if ($checklistTemplateMaster && $checklistTemplateMaster->exists) {
            $this->load($checklistTemplateMaster);

            return;
        }

        if (empty($this->items)) {
            $this->items = [
                ['label' => '', 'is_required' => true],
            ];
        }
    }

    protected function load(ChecklistTemplateMaster $r): void
    {
        $this->editingId = $r->id;
        $this->name = $r->name;
        $this->code = $r->code;
        $this->checklist_group_id = $r->checklist_group_id;
        $this->applies_to = $r->applies_to;
        $this->is_active = $r->is_active;
        $this->notes = $r->notes;
        $this->items = $this->normalizeItems($r->items ?? []);
    }

    public function addItem(): void
    {
        $this->items[] = ['label' => '', 'is_required' => true];
    }

    public function removeItem(int $i): void
    {
        if (! isset($this->items[$i])) {
            return;
        }
        array_splice($this->items, $i, 1);
        // Re-index keys.
        $this->items = array_values($this->items);
    }

    public function createChecklistGroup(): void
    {
        $this->quickCreate(
            modelClass: ChecklistGroupMaster::class,
            targetProperty: 'checklist_group_id',
            searchProperty: 'checklistGroupSearch',
            permission: 'checklist_group_master.create',
            label: 'Checklist group',
        );
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'checklist_template_master.update' : 'checklist_template_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields except enums/ids/array/booleans.
        $skip = ['checklist_group_id', 'applies_to', 'items', 'is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        // Capital typing on item labels too.
        $data['items'] = array_map(function (array $row) {
            return [
                'label' => strtoupper($row['label']),
                'is_required' => (bool) ($row['is_required'] ?? false),
            ];
        }, $data['items']);

        if ($this->editingId) {
            ChecklistTemplateMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Checklist template #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = ChecklistTemplateMaster::create($data);
            Flux::toast(text: 'Checklist template #'.$record->id.' created.', variant: 'success');
        }

        return redirect()->route('checklist-template-master.index');
    }

    /**
     * Normalize stored items into the expected shape (label string, is_required bool).
     *
     * @param  array<int, mixed>  $items
     * @return array<int, array{label: string, is_required: bool}>
     */
    protected function normalizeItems(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $out[] = [
                'label' => (string) ($item['label'] ?? ''),
                'is_required' => (bool) ($item['is_required'] ?? false),
            ];
        }

        if (empty($out)) {
            $out[] = ['label' => '', 'is_required' => true];
        }

        return $out;
    }

    public function render()
    {
        return view('checklist-template-master::edit', [
            'appliesToOptions' => ChecklistTemplateMaster::appliesToOptions(),
            'groupOptions' => ChecklistGroupMaster::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
