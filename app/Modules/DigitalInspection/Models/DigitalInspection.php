<?php

namespace App\Modules\DigitalInspection\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\DigitalInspection\Database\Factories\DigitalInspectionFactory;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Models\JobCard;
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

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'digital_inspections';

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static array $searchableFields = ['inspection_no', 'summary_notes'];

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
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_WIP => 'WIP',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Per-item outcome enumeration (Rep / Adj / OK / IA / FA + Pending sentinel).
     *
     * @return array<string, string>
     */
    public static function outcomes(): array
    {
        return [
            'pending' => 'Pending',
            'rep' => 'Replace / Repair',
            'adj' => 'Adjust',
            'ok' => 'OK',
            'ia' => 'Immediate Action',
            'fa' => 'Future Action',
        ];
    }
}
