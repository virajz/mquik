<?php

namespace App\Modules\EstimateTemplateMaster\Livewire;

use App\Modules\EstimateTemplateMaster\Models\EstimateTemplateMaster;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Estimate Template')]
class Edit extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public ?int $inventory_group_id = null;

    public bool $is_active = true;

    public ?string $notes = null;

    /** @var list<array{id: ?int, line_type: string, spare_id: ?int, labour_id: ?int, default_qty: float|string, sequence_no: int}> */
    public array $items = [];

    public function mount(?EstimateTemplateMaster $estimateTemplate = null): void
    {
        if ($estimateTemplate && $estimateTemplate->exists) {
            $this->load($estimateTemplate);
        }
    }

    protected function load(EstimateTemplateMaster $t): void
    {
        $t->load('items');
        $this->editingId = $t->id;
        $this->name = $t->name;
        $this->code = $t->code;
        $this->inventory_group_id = $t->inventory_group_id;
        $this->is_active = (bool) $t->is_active;
        $this->notes = $t->notes;

        $this->items = $t->items->map(fn ($i) => [
            'id' => $i->id,
            'line_type' => $i->line_type,
            'spare_id' => $i->spare_id,
            'labour_id' => $i->labour_id,
            'default_qty' => (float) $i->default_qty,
            'sequence_no' => (int) $i->sequence_no,
        ])->all();
    }

    public function addItem(string $type): void
    {
        $this->items[] = [
            'id' => null,
            'line_type' => $type,
            'spare_id' => null,
            'labour_id' => null,
            'default_qty' => 1,
            'sequence_no' => count($this->items) + 1,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('estimate_templates', 'name')->ignore($this->editingId)],
            'code' => ['nullable', 'string', 'max:32', Rule::unique('estimate_templates', 'code')->ignore($this->editingId)],
            'inventory_group_id' => ['nullable', 'integer', 'exists:inventory_groups,id'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['array'],
            'items.*.line_type' => ['required', 'in:spare,labour'],
            'items.*.spare_id' => ['nullable', 'integer', 'exists:spares,id', 'required_if:items.*.line_type,spare'],
            'items.*.labour_id' => ['nullable', 'integer', 'exists:labours,id', 'required_if:items.*.line_type,labour'],
            'items.*.default_qty' => ['numeric', 'min:0.01'],
        ];
    }

    #[Computed]
    public function inventoryGroups()
    {
        return InventoryGroupMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function spares()
    {
        return SpareMaster::query()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name']);
    }

    #[Computed]
    public function labours()
    {
        return LabourMaster::query()->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name']);
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'estimate_template_master.update' : 'estimate_template_master.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        unset($data['items']);

        foreach (['name', 'code', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        DB::transaction(function () use ($data, $items) {
            if ($this->editingId) {
                $t = EstimateTemplateMaster::findOrFail($this->editingId);
                $t->update($data);
            } else {
                $t = EstimateTemplateMaster::create($data);
                $this->editingId = $t->id;
            }

            $keptIds = [];
            foreach (array_values($items) as $i => $row) {
                $payload = [
                    'line_type' => $row['line_type'],
                    'spare_id' => $row['line_type'] === 'spare' ? ($row['spare_id'] ?? null) : null,
                    'labour_id' => $row['line_type'] === 'labour' ? ($row['labour_id'] ?? null) : null,
                    'default_qty' => $row['default_qty'] ?? 1,
                    'sequence_no' => $i + 1,
                ];
                if (! empty($this->items[$i]['id'])) {
                    $existing = $t->items()->whereKey($this->items[$i]['id'])->first();
                    if ($existing) {
                        $existing->update($payload);
                        $keptIds[] = $existing->id;

                        continue;
                    }
                }
                $created = $t->items()->create($payload);
                $keptIds[] = $created->id;
            }
            $t->items()->whereNotIn('id', $keptIds)->delete();
        });

        Flux::toast(text: 'Estimate template '.($isCreate ? 'created.' : 'updated.'), variant: 'success');

        return redirect()->route('estimate-template-master.index');
    }

    public function render()
    {
        return view('estimate-template-master::edit');
    }
}
