<?php

namespace App\Modules\LateMemo\Models;

use App\Concerns\Auditable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\LateMemo\Database\Factories\LateMemoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LateMemo extends Model
{
    use Auditable;
    use HasFactory;

    public const STATUS_ISSUED = 'issued';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_WAIVED = 'waived';

    protected $table = 'late_memos';

    protected $guarded = [];

    protected $casts = [
        'memo_date' => 'date',
        'late_by_minutes' => 'integer',
        'issued_at' => 'datetime',
    ];

    protected static function newFactory(): LateMemoFactory
    {
        return LateMemoFactory::new();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'issued_by_employee_id');
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ISSUED => 'Issued',
            self::STATUS_ACKNOWLEDGED => 'Acknowledged',
            self::STATUS_WAIVED => 'Waived',
        ];
    }
}
