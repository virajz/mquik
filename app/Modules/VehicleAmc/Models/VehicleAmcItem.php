<?php

namespace App\Modules\VehicleAmc\Models;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One included service / spare on an AMC package, with its discount.
 */
class VehicleAmcItem extends Model
{
    protected $table = 'vehicle_amc_items';

    protected $guarded = [];

    protected $casts = ['quantity' => 'decimal:2', 'discount_value' => 'decimal:2', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function itemTypes(): array
    {
        return ['spare' => 'Spare / Part', 'labour' => 'Labour / Service'];
    }

    public function amc(): BelongsTo
    {
        return $this->belongsTo(VehicleAmc::class, 'vehicle_amc_id');
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureMaster::class, 'uom_id');
    }

    public function hsn(): BelongsTo
    {
        return $this->belongsTo(HsnMaster::class, 'hsn_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(TaxMaster::class, 'tax_id');
    }
}
