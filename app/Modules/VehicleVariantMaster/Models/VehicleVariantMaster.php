<?php

namespace App\Modules\VehicleVariantMaster\Models;

use App\Concerns\Auditable;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Database\Factories\VehicleVariantMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleVariantMaster extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'vehicle_variants';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModelMaster::class, 'model_id');
    }

    protected static function newFactory(): VehicleVariantMasterFactory
    {
        return VehicleVariantMasterFactory::new();
    }
}
