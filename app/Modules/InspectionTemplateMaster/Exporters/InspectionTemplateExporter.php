<?php

namespace App\Modules\InspectionTemplateMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use Illuminate\Database\Eloquent\Builder;

class InspectionTemplateExporter implements Exportable
{
    public function label(): string
    {
        return 'Inspection Templates';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Applies To', 'Items', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return InspectionTemplateMaster::query()->with('items:id,name')->orderBy('name');
    }

    /**
     * @param  InspectionTemplateMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->code,
            $model->applies_to,
            $model->items->pluck('name')->implode(', '),
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'inspection-templates';
    }
}
