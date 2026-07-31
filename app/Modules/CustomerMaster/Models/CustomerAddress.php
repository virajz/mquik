<?php

namespace App\Modules\CustomerMaster\Models;

use App\Concerns\Auditable;
use App\Modules\CustomerMaster\Database\Factories\CustomerAddressFactory;
use App\Modules\RegionMaster\Models\RegionMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'customer_addresses';

    protected $guarded = [];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(RegionMaster::class, 'region_id');
    }

    /**
     * Walk the region's parent chain (pincode → area → city → state) and
     * return a comma-joined display string. Empty if no region.
     */
    public function regionChain(): string
    {
        if (! $this->relationLoaded('region') && ! $this->region_id) {
            return '';
        }

        $names = [];
        $node = $this->region;
        $depth = 0;
        while ($node && $depth < 6) {
            $names[] = $node->name;
            $node = $node->parent;
            $depth++;
        }

        return implode(', ', $names);
    }

    /** The flattened one-line address (street + region chain), resolved live. */
    public function fullAddress(): string
    {
        return trim(($this->address_line ?? '').($this->regionChain() ? ', '.$this->regionChain() : ''));
    }

    protected static function newFactory(): CustomerAddressFactory
    {
        return CustomerAddressFactory::new();
    }
}
