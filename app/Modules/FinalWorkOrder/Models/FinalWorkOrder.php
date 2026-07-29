<?php

namespace App\Modules\FinalWorkOrder\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\BayMaster\Models\BayMaster;
use App\Modules\DelayReasonMaster\Models\DelayReasonMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FinalWorkOrder\Database\Factories\FinalWorkOrderFactory;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\JobHistory\Support\JobCardHistoryRecorder;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\ReworkReasonMaster\Models\ReworkReasonMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\TechnicianFinding\Models\TechnicianFinding;
use App\Modules\WorkOrderHoldReasonMaster\Models\WorkOrderHoldReasonMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinalWorkOrder extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_ASSIGNMENT_PENDING = 'assignment_pending';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_WIP = 'wip';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'final_work_orders';

    protected $guarded = [];

    protected $casts = [
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    protected static array $searchableFields = ['order_no', 'notes'];

    protected static function newFactory(): FinalWorkOrderFactory
    {
        return FinalWorkOrderFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->order_no === null) {
                $row->forceFill([
                    'order_no' => 'FWO-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });

        static::updated(function (self $row) {
            $changed = $row->getChanges();
            if (! isset($changed['status']) || $row->getOriginal('status') === $row->status) {
                return;
            }

            $eventType = match ($row->status) {
                self::STATUS_ASSIGNED => JobCardHistoryEvent::TYPE_WORK_ORDER_ASSIGNED,
                self::STATUS_WIP => JobCardHistoryEvent::TYPE_WORK_ORDER_STARTED,
                self::STATUS_ON_HOLD => JobCardHistoryEvent::TYPE_WORK_ORDER_HELD,
                self::STATUS_COMPLETED => JobCardHistoryEvent::TYPE_WORK_ORDER_COMPLETED,
                default => JobCardHistoryEvent::TYPE_STATUS_CHANGED,
            };

            JobCardHistoryRecorder::record(
                (int) $row->job_card_id,
                $eventType,
                'Work Order '.$row->order_no.': '.(self::statuses()[$row->status] ?? $row->status),
                ['order_id' => $row->id, 'from' => $row->getOriginal('status'), 'to' => $row->status],
            );
        });
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'department_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'advisor_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'technician_id');
    }

    public function bay(): BelongsTo
    {
        return $this->belongsTo(BayMaster::class, 'bay_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(InspectionTemplateMaster::class, 'inspection_template_id');
    }

    public function holdReason(): BelongsTo
    {
        return $this->belongsTo(WorkOrderHoldReasonMaster::class, 'hold_reason_id');
    }

    public function reworkReason(): BelongsTo
    {
        return $this->belongsTo(ReworkReasonMaster::class, 'rework_reason_id');
    }

    public function delayReason(): BelongsTo
    {
        return $this->belongsTo(DelayReasonMaster::class, 'delay_reason_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FinalWorkOrderItem::class, 'final_work_order_id')->orderBy('sequence_no');
    }

    public function pauses(): HasMany
    {
        return $this->hasMany(FinalWorkOrderPause::class, 'final_work_order_id')->orderBy('paused_at');
    }

    /** Total paused minutes across all pause/resume rows. */
    public function pauseMinutes(): int
    {
        return (int) $this->pauses->reduce(function (int $carry, FinalWorkOrderPause $p) {
            if ($p->paused_at && $p->resumed_at) {
                return $carry + $p->paused_at->diffInMinutes($p->resumed_at);
            }

            return $carry;
        }, 0);
    }

    /** Gross elapsed working minutes (start → end). Null if not both set. */
    public function grossMinutes(): ?int
    {
        if (! $this->started_at || ! $this->ended_at) {
            return null;
        }

        return (int) $this->started_at->diffInMinutes($this->ended_at);
    }

    /** Net turnaround minutes = gross − paused. Null while still open. */
    public function netTatMinutes(): ?int
    {
        $gross = $this->grossMinutes();

        return $gross === null ? null : max(0, $gross - $this->pauseMinutes());
    }

    /** Sum of technician hours logged across items. */
    public function totalItemHours(): float
    {
        return round((float) $this->items->sum(fn ($i) => (float) $i->hours), 2);
    }

    /** What this order is inspecting — complaints, job descriptions, packages. */
    public function workScopes(): HasMany
    {
        return $this->hasMany(FinalWorkOrderScope::class, 'final_work_order_id')->orderBy('sequence_no');
    }

    /** Order-level photo evidence, separate from the per-item before/after pair. */
    public function photos(): HasMany
    {
        return $this->hasMany(FinalWorkOrderPhoto::class, 'final_work_order_id')->orderBy('sequence_no');
    }

    /**
     * Extra work discovered during the inspection. Recorded by the Technician
     * Findings module rather than duplicated here — this is the "additional
     * work" side of the order.
     */
    public function findings(): HasMany
    {
        return $this->hasMany(TechnicianFinding::class, 'final_work_order_id');
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ASSIGNMENT_PENDING => 'Assignment Pending',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_WIP => 'Work In Progress',
            self::STATUS_ON_HOLD => 'Work On Hold',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function completionTypes(): array
    {
        return [
            'fully' => 'Fully Completed',
            'partially' => 'Partially Completed',
            'deferred' => 'Deferred',
        ];
    }

    /**
     * Per-item inspection result (CSV: IA / FA / OK + pending sentinel).
     *
     * @return array<string, string>
     */
    public static function results(): array
    {
        return [
            'pending' => 'Pending',
            'ok' => 'OK',
            'ia' => 'Immediate Action',
            'fa' => 'Future Action',
        ];
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(PriorityMaster::class, 'priority_id');
    }
}
