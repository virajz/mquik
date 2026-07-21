<?php

namespace App\Modules\GateMaster\Exporters;

use App\Modules\GateMaster\Models\GateMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class GateExporter implements Exportable
{
    public function label(): string
    {
        return 'Gates';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return GateMaster::query()->orderBy('name');
    }

    /**
     * @param  GateMaster  $model
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
        return 'gates';
    }
}
