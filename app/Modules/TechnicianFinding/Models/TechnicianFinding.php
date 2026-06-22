<?php

namespace App\Modules\TechnicianFinding\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\JobHistory\Support\JobCardHistoryRecorder;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TechnicianFinding\Database\Factories\TechnicianFindingFactory;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianFinding extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const TYPE_SPARE = 'spare';

    public const TYPE_LABOUR = 'labour';

    public const STATUS_RECOMMENDED = 'recommended';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CONVERTED = 'converted';

    protected $table = 'technician_findings';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'estimated_amount' => 'decimal:2',
    ];

    protected static array $searchableFields = ['finding_no', 'description', 'notes'];

    protected static function newFactory(): TechnicianFindingFactory
    {
        return TechnicianFindingFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->finding_no === null) {
                $row->forceFill([
                    'finding_no' => 'TF-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }

            JobCardHistoryRecorder::record(
                (int) $row->job_card_id,
                JobCardHistoryEvent::TYPE_FINDING_RECORDED,
                'Technician finding '.($row->finding_no ?? 'TF-'.$row->id).': '.$row->description,
                ['finding_id' => $row->id, 'type' => $row->finding_type],
            );
        });
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(VehicleInspectionOrder::class, 'vehicle_inspection_order_id');
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function labour(): BelongsTo
    {
        return $this->belongsTo(LabourMaster::class, 'labour_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'reported_by_id');
    }

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_SPARE => 'Spare / Part',
            self::TYPE_LABOUR => 'Labour',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_RECOMMENDED => 'Recommended',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CONVERTED => 'Converted',
        ];
    }

    /**
     * CSV: Additional Work Recommendation.
     *
     * @return array<string, string>
     */
    public static function recommendations(): array
    {
        return [
            'new_issue' => 'New Issue Found',
            'additional_parts' => 'Additional Parts Required',
            'additional_labour' => 'Additional Labour Required',
        ];
    }
}
