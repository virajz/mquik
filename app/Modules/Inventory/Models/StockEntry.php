<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Inventory\Database\Factories\StockEntryFactory;
use App\Modules\SpareMaster\Models\SpareMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockEntry extends Model
{
    use HasFactory;

    // Entry types — IN (positive qty) and OUT (negative qty)
    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_PURCHASE_RETURN = 'purchase_return';

    public const TYPE_SALE = 'sale';

    public const TYPE_SALE_RETURN = 'sale_return';

    public const TYPE_CHALLAN_OUT = 'challan_out';

    public const TYPE_CHALLAN_RETURN = 'challan_return';

    public const TYPE_IPO_ISSUE = 'ipo_issue';

    public const TYPE_IPO_RETURN = 'ipo_return';

    public const TYPE_CONSUMPTION = 'consumption';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_OPENING = 'opening';

    protected $table = 'stock_entries';

    protected static function newFactory(): StockEntryFactory
    {
        return StockEntryFactory::new();
    }

    protected $guarded = [];

    protected $casts = [
        'qty' => 'decimal:2',
        'rate_per_unit' => 'decimal:2',
        'moved_at' => 'datetime',
    ];

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo('source');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * Record a stock movement. Called by any module that affects stock.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function record(
        int $spareId,
        string $entryType,
        float $qty,
        float $ratePerUnit = 0,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $location = null,
        ?string $notes = null,
    ): self {
        return self::create([
            'spare_id' => $spareId,
            'entry_type' => $entryType,
            'qty' => $qty,
            'rate_per_unit' => $ratePerUnit,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'location' => $location,
            'moved_at' => now(),
            'actor_user_id' => auth()->id(),
            'notes' => $notes,
        ]);
    }
}
