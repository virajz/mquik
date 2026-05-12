<?php

namespace App\Modules\VendorMaster\Models;

use App\Concerns\Auditable;
use App\Modules\VendorMaster\Database\Factories\VendorTermFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorTerm extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'vendor_terms';

    protected $guarded = [];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    protected static function newFactory(): VendorTermFactory
    {
        return VendorTermFactory::new();
    }
}
