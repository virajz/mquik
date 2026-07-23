<?php

namespace App\Modules\SpareBrandMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\SpareBrandMaster\Database\Factories\SpareBrandMasterFactory;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SpareBrandMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'spare_brands';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): SpareBrandMasterFactory
    {
        return SpareBrandMasterFactory::new();
    }

    /**
     * Vendors who supply this parts brand. Suppliers are tracked at brand level
     * rather than per part number — that is where terms are actually agreed.
     */
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(
            VendorMaster::class,
            'spare_brand_vendor',
            'spare_brand_id',
            'vendor_id',
        );
    }
}
