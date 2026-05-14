<?php

namespace App\Modules\ServicePackageMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ServicePackageMaster\Database\Factories\ServicePackageMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePackageMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'service_packages';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_amc' => 'boolean',
        'total_price' => 'decimal:2',
    ];

    protected static array $searchableFields = ['name', 'code', 'description'];

    protected static function newFactory(): ServicePackageMasterFactory
    {
        return ServicePackageMasterFactory::new();
    }

    public function services(): HasMany
    {
        return $this->hasMany(ServicePackageService::class, 'service_package_id')->orderBy('sequence_no');
    }
}
