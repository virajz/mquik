<?php

namespace App\Modules\HsnMaster\Exporters;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class HsnExporter implements Exportable
{
    public function label(): string
    {
        return 'HSN / SAC Codes';
    }

    public function headers(): array
    {
        return ['ID', 'Code', 'Description', 'Kind', 'GST %', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return HsnMaster::query()->orderBy('code');
    }

    /**
     * @param  HsnMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->code,
            $model->name,
            $model->kind,
            $model->gst_percent,
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'hsn-codes';
    }
}
