<?php

namespace App\Modules\BankMaster\Exporters;

use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class BankExporter implements Exportable
{
    public function label(): string
    {
        return 'Banks';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return BankMaster::query()->orderBy('name');
    }

    /**
     * @param  BankMaster  $model
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
        return 'banks';
    }
}
