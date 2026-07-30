<?php

namespace App\Modules\StockCounting\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\RackMaster\Models\RackMaster;
use App\Modules\StockCounting\Database\Factories\StockCountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCount extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const METHOD_QR_BARCODE = 'qr_barcode';

    public const METHOD_RFID = 'rfid';

    public const METHOD_MANUAL = 'manual';

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'stock_counts';

    protected $guarded = [];

    protected $casts = [
        'count_start_date' => 'date',
        'count_end_date' => 'date',
    ];

    protected static array $searchableFields = ['count_no', 'team_name', 'notes'];

    protected static function newFactory(): StockCountFactory
    {
        return StockCountFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->count_no === null) {
                $row->forceFill([
                    'count_no' => 'SC-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function countingMethods(): array
    {
        return [
            self::METHOD_QR_BARCODE => 'QR / Barcode',
            self::METHOD_RFID => 'RFID',
            self::METHOD_MANUAL => 'Manual',
        ];
    }

    /** @return array<string, string> */
    public static function verificationStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(RackMaster::class, 'storage_location_id');
    }

    public function inventoryGroup(): BelongsTo
    {
        return $this->belongsTo(InventoryGroupMaster::class, 'inventory_group_id');
    }

    public function teamLeader(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'team_leader_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockCountItem::class, 'stock_count_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(StockCountAttachment::class, 'stock_count_id')->orderBy('sequence_no');
    }
}
