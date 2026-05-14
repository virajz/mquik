<?php

namespace App\Modules\ServicePackageMaster\Models;

use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePackageService extends Model
{
    protected $table = 'service_package_services';

    protected $guarded = [];

    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackageMaster::class, 'service_package_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }
}
