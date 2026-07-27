<?php

namespace App\Modules\EstimateTemplateMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EstimateTemplateMaster\Database\Factories\EstimateTemplateMasterFactory;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstimateTemplateMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'estimate_templates';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_date' => 'date',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): EstimateTemplateMasterFactory
    {
        return EstimateTemplateMasterFactory::new();
    }

    /** @return array<string, string> */
    public static function categories(): array
    {
        return [
            'pms' => 'PMS',
            'brake' => 'Brake',
            'suspension' => 'Suspension',
            'clutch' => 'Clutch',
            'denting' => 'Denting',
            'painting' => 'Painting',
        ];
    }

    public function inventoryGroup(): BelongsTo
    {
        return $this->belongsTo(InventoryGroupMaster::class, 'inventory_group_id');
    }

    public function vehicleBrand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrandMaster::class, 'vehicle_brand_id');
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModelMaster::class, 'vehicle_model_id');
    }

    public function vehicleVariant(): BelongsTo
    {
        return $this->belongsTo(VehicleVariantMaster::class, 'vehicle_variant_id');
    }

    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackageMaster::class, 'service_package_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EstimateTemplateItem::class, 'estimate_template_id')->orderBy('sequence_no');
    }
}
