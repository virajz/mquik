<?php

namespace App\Modules\CourierCompanyMaster\Exporters;

use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class CourierCompanyExporter implements Exportable
{
    public function label(): string
    {
        return 'Courier Companies';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return CourierCompanyMaster::query()->orderBy('name');
    }

    /**
     * @param  CourierCompanyMaster  $model
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
        return 'courier-companies';
    }
}
