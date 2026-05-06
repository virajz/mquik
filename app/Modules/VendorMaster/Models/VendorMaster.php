<?php

namespace App\Modules\VendorMaster\Models;

use App\Concerns\Searchable;
use App\Modules\VendorMaster\Database\Factories\VendorMasterFactory;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorMaster extends Model
{
    use HasFactory;
    use Searchable;

    protected $table = 'vendors';

    protected static array $searchableFields = ['name', 'vendor_code', 'phone', 'gstin'];

    public function toSearchResult(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->name,
            'subtitle' => $this->vendor_code.($this->phone ? ' • +91 '.$this->phone : ''),
        ];
    }

    protected $guarded = [];

    protected $casts = [
        'credit_days' => 'integer',
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function vendorType(): BelongsTo
    {
        return $this->belongsTo(VendorTypeMaster::class, 'vendor_type_id');
    }

    protected static function newFactory(): VendorMasterFactory
    {
        return VendorMasterFactory::new();
    }
}
