<?php

namespace App\Modules\FollowUpModeMaster\Exporters;

use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class FollowUpModeExporter implements Exportable
{
    public function label(): string
    {
        return 'Follow-up Modes';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return FollowUpModeMaster::query()->orderBy('name');
    }

    /**
     * @param  FollowUpModeMaster  $model
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
        return 'follow-up-modes';
    }
}
