<?php

namespace App\Modules\ChallanReasonMaster\Exporters;

use App\Modules\ChallanReasonMaster\Models\ChallanReasonMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class ChallanReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Challan Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ChallanReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  ChallanReasonMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->code,
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'challan-reasons';
    }
}
