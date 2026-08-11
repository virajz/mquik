<?php

namespace App\Modules\NotificationCenter\Models;

use App\Models\User;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    public const TYPE_PARTS_INQUIRY_RAISED = 'parts_inquiry_raised';

    public const TYPE_PARTS_RESPONSE = 'parts_response';

    public const TYPE_GOODS_RECEIVED = 'goods_received';

    public const TYPE_GOODS_HANDED_OVER = 'goods_handed_over';

    public const TYPE_FINDING_RECORDED = 'finding_recorded';

    public const TYPE_FINDINGS_REPORT = 'findings_report';

    public const TYPE_VENDOR_ORDER_PLACED = 'vendor_order_placed';

    public const TYPE_CUSTOMER_APPROVAL = 'customer_approval';

    public const TYPE_DELIVERY_DELAYED = 'delivery_delayed';

    public const TYPE_PARTS_NOT_RECEIVED = 'parts_not_received';

    public const TYPE_APPROVAL_PENDING = 'approval_pending';

    public const TYPE_DOCUMENT_PENDING = 'document_pending';

    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_CRITICAL = 'critical';

    protected $table = 'app_notifications';

    protected $guarded = [];

    protected $casts = [
        'read_at' => 'datetime',
        'resolved_at' => 'datetime',
        'requires_action' => 'boolean',
    ];

    /** @return array<string, string> */
    public static function types(): array
    {
        return [
            self::TYPE_PARTS_INQUIRY_RAISED => 'Parts Inquiry Raised',
            self::TYPE_PARTS_RESPONSE => 'Store Responded',
            self::TYPE_GOODS_RECEIVED => 'Goods Received',
            self::TYPE_GOODS_HANDED_OVER => 'Goods Handed Over',
            self::TYPE_FINDING_RECORDED => 'Technician Finding',
            self::TYPE_FINDINGS_REPORT => 'Findings Report',
            self::TYPE_VENDOR_ORDER_PLACED => 'Vendor Order Placed',
            self::TYPE_CUSTOMER_APPROVAL => 'Customer Approval',
            self::TYPE_DELIVERY_DELAYED => 'Delivery Delayed',
            self::TYPE_PARTS_NOT_RECEIVED => 'Parts Not Received',
            self::TYPE_APPROVAL_PENDING => 'Approval Pending',
            self::TYPE_DOCUMENT_PENDING => 'Document Pending',
        ];
    }

    public function isOpen(): bool
    {
        return $this->requires_action && $this->resolved_at === null;
    }

    /** @param  Builder<AppNotification>  $query */
    public function scopeNeedsAction(Builder $query): Builder
    {
        return $query->where('requires_action', true)->whereNull('resolved_at');
    }

    /**
     * Addressed to this person, or to the job they are doing.
     *
     * @param  Builder<AppNotification>  $query
     */
    public function scopeAddressedTo(Builder $query, ?int $employeeId, ?string $role = null): Builder
    {
        return $query->where(function (Builder $q) use ($employeeId, $role) {
            if ($employeeId) {
                $q->orWhere('employee_id', $employeeId);
            }
            if ($role) {
                $q->orWhere('role', $role);
            }
            if (! $employeeId && ! $role) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /** @param  Builder<AppNotification>  $query */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /** @param  Builder<AppNotification>  $query */
    public function scopeForEmployee(Builder $query, ?int $employeeId): Builder
    {
        return $employeeId
            ? $query->where('employee_id', $employeeId)
            : $query->whereRaw('1 = 0');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
