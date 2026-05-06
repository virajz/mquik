<?php

namespace App\Modules\EnquirySourceMaster\Exporters;

use App\Modules\EnquirySourceMaster\Models\EnquirySourceMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class EnquirySourceExporter implements Exportable
{
    public function label(): string
    {
        return 'Enquiry Sources';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return EnquirySourceMaster::query()->orderBy('name');
    }

    /**
     * @param  EnquirySourceMaster  $model
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
        return 'enquiry-sources';
    }
}
