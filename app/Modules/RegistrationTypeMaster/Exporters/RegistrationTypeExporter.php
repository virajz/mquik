<?php

namespace App\Modules\RegistrationTypeMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\RegistrationTypeMaster\Models\RegistrationTypeMaster;
use Illuminate\Database\Eloquent\Builder;

class RegistrationTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Registration Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return RegistrationTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  RegistrationTypeMaster  $model
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
        return 'registration-types';
    }
}
