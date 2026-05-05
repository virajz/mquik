<?php

namespace App\Modules\VehicleVariantMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Database\Eloquent\Builder;

class VehicleVariantExporter implements Exportable
{
    public function label(): string
    {
        return 'Vehicle Variants';
    }

    public function headers(): array
    {
        return ['ID', 'Brand', 'Model', 'Variant', 'Transmission', 'Engine', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return VehicleVariantMaster::query()->with('model.brand')->orderBy('name');
    }

    public function row(object $model): array
    {
        return [
            $model->id,
            $model->model?->brand?->name,
            $model->model?->name,
            $model->name,
            $model->transmission,
            $model->engine_cc,
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'vehicle-variants';
    }
}
