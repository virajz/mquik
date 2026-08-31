<?php

namespace App\Modules\DigitalInspection\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\DigitalInspection\Database\Factories\DigitalInspectionFactory;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\JobHistory\Support\JobCardHistoryRecorder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DigitalInspection extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_WIP = 'wip';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const ACTION_IMMEDIATE = 'ia';

    public const ACTION_FUTURE = 'fa';

    /** No attention: nothing to recommend and nothing to rate. */
    public const ACTION_NONE = 'na';

    protected $table = 'digital_inspections';

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static array $searchableFields = ['inspection_no', 'summary_notes', 'registration_no', 'jobCard.job_card_no'];

    protected static function newFactory(): DigitalInspectionFactory
    {
        return DigitalInspectionFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->inspection_no === null) {
                $row->forceFill([
                    'inspection_no' => 'DI-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }

            if ($row->status === self::STATUS_WIP) {
                JobCardHistoryRecorder::record(
                    (int) $row->job_card_id,
                    JobCardHistoryEvent::TYPE_INSPECTION_STARTED,
                    'Inspection '.($row->inspection_no ?? 'DI-'.$row->id).' started',
                    ['inspection_id' => $row->id],
                );
            }
        });

        static::updated(function (self $row) {
            $changed = $row->getChanges();
            if (! isset($changed['status'])) {
                return;
            }
            $original = $row->getOriginal('status');
            if ($original === $row->status) {
                return;
            }

            if ($row->status === self::STATUS_WIP) {
                JobCardHistoryRecorder::record(
                    (int) $row->job_card_id,
                    JobCardHistoryEvent::TYPE_INSPECTION_STARTED,
                    'Inspection '.$row->inspection_no.' started',
                    ['inspection_id' => $row->id],
                );
            } elseif ($row->status === self::STATUS_COMPLETED) {
                JobCardHistoryRecorder::record(
                    (int) $row->job_card_id,
                    JobCardHistoryEvent::TYPE_INSPECTION_COMPLETED,
                    'Inspection '.$row->inspection_no.' completed',
                    ['inspection_id' => $row->id],
                );
            }
        });
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(InspectionTemplateMaster::class, 'inspection_template_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'assigned_technician_id');
    }

    public function floorIncharge(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'floor_incharge_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DigitalInspectionItem::class, 'digital_inspection_id')->orderBy('sequence_no');
    }

    /**
     * @return array<string, string>
     */
    /**
     * Approving or rejecting an inspection is not a thing that happens here —
     * the sheet records what was found, and the decision belongs to the
     * estimate. The constants stay so historic rows still render.
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_WIP => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** Including the retired values, for rendering rows that still carry them. */
    public static function allStatuses(): array
    {
        return self::statuses() + [
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    /**
     * What the technician decided about a checkpoint — the Action Type.
     *
     * Three answers, not fifteen: does it need attention now, later, or not at
     * all. The old condition words (OK / Good / Poor / Faulty / Adjust …) said
     * how the part looked without saying what to do about it, which is the only
     * thing the advisor and the customer act on.
     *
     * @return array<string, string>
     */
    public static function outcomes(): array
    {
        return [
            'pending' => 'Pending',
            self::ACTION_IMMEDIATE => 'IA — Immediate Attention',
            self::ACTION_FUTURE => 'FA — Future Attention',
            self::ACTION_NONE => 'NA — No Attention',
        ];
    }

    /** Including the retired condition words, so historic rows still render. */
    public static function allOutcomes(): array
    {
        return self::outcomes() + [
            'ok' => 'OK',
            'good' => 'Good',
            'excellent' => 'Excellent',
            'average' => 'Average',
            'poor' => 'Poor',
            'critical' => 'Critical',
            'not_ok' => 'Not OK',
            'faulty' => 'Faulty',
            'adj' => 'Adjust',
            'rep' => 'Repair / Replace',
            'not_checked' => 'Not Checked',
        ];
    }

    /**
     * What to do about it. "No Action Required" and "Urgent Attention" are gone:
     * both restate the Action Type rather than naming a job.
     *
     * @return array<string, string>
     */
    public static function recommendations(): array
    {
        return [
            'repair' => 'Repair',
            'replace' => 'Replace',
            'skimming' => 'Skimming',
            'monitor' => 'Monitor',
        ];
    }

    /** Including the retired values, so historic rows still render. */
    public static function allRecommendations(): array
    {
        return self::recommendations() + [
            'none' => 'No Action Required',
            'urgent' => 'Urgent Attention',
        ];
    }

    /**
     * How bad it is. Follows the Action Type by default — High for immediate,
     * Low for future — and the technician can move it.
     *
     * @return array<string, string>
     */
    public static function severities(): array
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
        ];
    }

    /** The severity an action type implies, before the technician overrides it. */
    public static function severityForAction(?string $action): ?string
    {
        return match ($action) {
            self::ACTION_IMMEDIATE => 'high',
            self::ACTION_FUTURE => 'low',
            default => null,
        };
    }
}
