<?php

namespace App\Modules\TransportModeMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\TransportModeMaster\Models\TransportModeMaster;
use Illuminate\Database\Eloquent\Builder;

class TransportModeExporter implements Exportable
{
    public function label(): string
    {
        return 'Transport Modes';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return TransportModeMaster::query()->orderBy('name');
    }

    /**
     * @param  TransportModeMaster  $model
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
        return 'transport-modes';
    }
}
