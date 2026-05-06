<?php

namespace App\Modules\VendorTypeMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use Illuminate\Database\Eloquent\Builder;

class VendorTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Vendor Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return VendorTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  VendorTypeMaster  $model
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
        return 'vendor-types';
    }
}
