<?php

namespace App\Modules\Attendance\Models;

use App\Concerns\Auditable;
use App\Modules\Attendance\Database\Factories\AttendanceFactory;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use Auditable;
    use HasFactory;

    public const TYPE_IN = 'in';

    public const TYPE_OUT = 'out';

    protected $table = 'attendances';

    protected $guarded = [];

    protected $casts = [
        'punched_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    protected static function newFactory(): AttendanceFactory
    {
        return AttendanceFactory::new();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
    }

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_IN => 'Punch In',
            self::TYPE_OUT => 'Punch Out',
        ];
    }
}
