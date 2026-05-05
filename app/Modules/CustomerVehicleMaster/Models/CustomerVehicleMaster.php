<?php

namespace App\Modules\CustomerVehicleMaster\Models;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Database\Factories\CustomerVehicleMasterFactory;
use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerVehicleMaster extends Model
{
    use HasFactory;

    protected $table = 'customer_vehicles';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'insurance_expiry' => 'date',
        'puc_expiry' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModelMaster::class, 'model_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(VehicleVariantMaster::class, 'variant_id');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(VehicleColorMaster::class, 'color_id');
    }

    protected static function newFactory(): CustomerVehicleMasterFactory
    {
        return CustomerVehicleMasterFactory::new();
    }
}
