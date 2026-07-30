<?php

namespace App\Modules\ServicePackageMaster\Models;

use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePackageService extends Model
{
    protected $table = 'service_package_services';

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
        'due_after_months' => 'integer',
        'due_after_km' => 'integer',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackageMaster::class, 'service_package_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }
}
