<?php

namespace App\Modules\LeaveManagement\Models;

use App\Concerns\Auditable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\LeaveManagement\Database\Factories\LeaveManagementFactory;
use App\Modules\LeaveTypeMaster\Models\LeaveTypeMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveManagement extends Model
{
    use Auditable;
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'leave_requests';

    protected $guarded = [];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'days_count' => 'float',
        'approved_at' => 'datetime',
    ];

    protected static function newFactory(): LeaveManagementFactory
    {
        return LeaveManagementFactory::new();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'approved_by_employee_id');
    }

    /**
     * Leave types are now sourced from the editable LeaveTypeMaster (keyed by code).
     *
     * @return array<string, string>
     */
    public static function leaveTypes(): array
    {
        return LeaveTypeMaster::query()
            ->where('is_active', true)
            ->whereNotNull('code')
            ->orderBy('name')
            ->pluck('name', 'code')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
}
