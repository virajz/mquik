<?php

namespace App\Modules\SpareMaster\Models;

use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One dated purchase-rate revision for a spare. The spare itself carries the
 * latest revision; everything older lives here so price movement stays visible.
 */
class SpareRateHistory extends Model
{
    /** Carried over from the client's previous ERP (its `WEF` rows). */
    public const SOURCE_LEGACY_IMPORT = 'legacy_import';

    /** Recorded from within Mquik. */
    public const SOURCE_MANUAL = 'manual';

    protected $table = 'spare_rate_history';

    protected $guarded = [];

    protected $casts = [
        'rate_before_tax' => 'decimal:2',
        'mrp' => 'decimal:2',
        'effective_from' => 'date',
    ];

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(SpareBrandMaster::class, 'spare_brand_id');
    }
}
