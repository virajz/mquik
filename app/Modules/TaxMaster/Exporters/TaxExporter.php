<?php

namespace App\Modules\TaxMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\TaxMaster\Models\TaxMaster;
use Illuminate\Database\Eloquent\Builder;

class TaxExporter implements Exportable
{
    public function label(): string
    {
        return 'Taxes';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'HSN/SAC', 'GST %', 'Cess %', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return TaxMaster::query()->orderBy('name');
    }

    /**
     * @param  TaxMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->code,
            $model->hsn_sac,
            number_format((float) $model->gst_percent, 2),
            number_format((float) $model->cess_percent, 2),
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'taxes';
    }
}
