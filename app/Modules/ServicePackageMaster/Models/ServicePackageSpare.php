<?php

namespace App\Modules\ServicePackageMaster\Models;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One included spare / part on a service package, with its pricing.
 */
class ServicePackageSpare extends Model
{
    protected $table = 'service_package_spares';

    protected $guarded = [];

    protected $casts = [
        'rate' => 'decimal:2',
        'quantity' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'taxable_value' => 'decimal:2',
        'net_price' => 'decimal:2',
        'offer_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'saving_price' => 'decimal:2',
        'sequence_no' => 'integer',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackageMaster::class, 'service_package_id');
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
