<?php

namespace App\Modules\AccountGroupMaster\Exporters;

use App\Modules\AccountGroupMaster\Models\AccountGroupMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class AccountGroupExporter implements Exportable
{
    public function label(): string
    {
        return 'Account Groups';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return AccountGroupMaster::query()->orderBy('name');
    }

    /**
     * @param  AccountGroupMaster  $model
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
        return 'account-groups';
    }
}
