<?php

namespace App\Modules\ChecklistTemplateMaster\Exporters;

use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class ChecklistTemplateExporter implements Exportable
{
    public function label(): string
    {
        return 'Checklist Templates';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Group', 'Applies To', 'Items', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ChecklistTemplateMaster::query()->with('group:id,name')->orderBy('name');
    }

    /**
     * @param  ChecklistTemplateMaster  $model
     */
    public function row(object $model): array
    {
        $items = collect($model->items ?? [])
            ->map(fn ($i) => ($i['label'] ?? '').':'.((($i['is_required'] ?? false) ? 'REQ' : 'OPT')))
            ->implode('|');

        return [
            $model->id,
            $model->name,
            $model->code,
            $model->group?->name,
            $model->applies_to,
            $items,
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'checklist-templates';
    }
}
