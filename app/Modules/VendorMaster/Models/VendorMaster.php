<?php

namespace App\Modules\VendorMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\RegionMaster\Models\RegionMaster;
use App\Modules\VendorMaster\Database\Factories\VendorMasterFactory;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class VendorMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'vendors';

    protected static array $searchableFields = ['name', 'vendor_code', 'phone', 'email', 'gstin'];

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

    public function vendorTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            VendorTypeMaster::class,
            'vendor_vendor_type',
            'vendor_id',
            'vendor_type_id',
        );
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(RegionMaster::class, 'region_id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(BankMaster::class, 'bank_id');
    }

    public function terms(): HasMany
    {
        return $this->hasMany(VendorTerm::class, 'vendor_id')->orderBy('sort_order')->orderBy('id');
    }

    protected static function newFactory(): VendorMasterFactory
    {
        return VendorMasterFactory::new();
    }

    /**
     * Wipe KYC files from storage when the vendor record is deleted, so
     * orphaned blobs don't accumulate on S3 / disk.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $vendor) {
            foreach (['aadhar_file_path', 'pan_file_path'] as $col) {
                if ($vendor->{$col}) {
                    Storage::delete($vendor->{$col});
                }
            }
        });
    }
}
