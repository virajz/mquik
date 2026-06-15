<?php

namespace App\Modules\RequestedRepairMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\RequestedRepairMaster\Models\RequestedRepairMaster;
use Illuminate\Database\Eloquent\Builder;

class RequestedRepairExporter implements Exportable
{
    public function label(): string
    {
        return 'Requested Repairs';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return RequestedRepairMaster::query()->orderBy('name');
    }

    /**
     * @param  RequestedRepairMaster  $model
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
        return 'requested-repairs';
    }
}
