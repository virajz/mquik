<?php

namespace App\Modules\HsnMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\HsnMaster\Database\Factories\HsnMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HsnMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'hsn_codes';

    protected $guarded = [];

    /** Goods. */
    public const KIND_HSN = 'hsn';

    /** Services. */
    public const KIND_SAC = 'sac';

    protected $casts = [
        'is_active' => 'boolean',
        'gst_percent' => 'decimal:2',
    ];

    protected static array $searchableFields = ['code', 'name'];

    protected static function newFactory(): HsnMasterFactory
    {
        return HsnMasterFactory::new();
    }

    /**
     * @return array<string, string>
     */
    public static function kinds(): array
    {
        return [
            self::KIND_HSN => 'HSN (goods)',
            self::KIND_SAC => 'SAC (services)',
        ];
    }

    /** "8708 — PARTS AND ACCESSORIES OF MOTOR VEHICLES" */
    public function label(): string
    {
        return $this->code.' — '.$this->name;
    }
}
