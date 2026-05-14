<?php

namespace App\Modules\InternalPartsInquiry\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InternalPartsInquiry\Database\Factories\InternalPartsInquiryFactory;
use App\Modules\JobCard\Models\JobCard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalPartsInquiry extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_OPEN = 'open';

    public const STATUS_RESPONDED = 'responded';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'internal_parts_inquiries';

    protected $guarded = [];

    protected $casts = [
        'requested_at' => 'datetime',
        'needed_by' => 'datetime',
    ];

    protected static array $searchableFields = ['ipi_no', 'notes'];

    protected static function newFactory(): InternalPartsInquiryFactory
    {
        return InternalPartsInquiryFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->ipi_no === null) {
                $row->forceFill([
                    'ipi_no' => 'IPI-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'requested_by_employee_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'target_employee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InternalPartsInquiryItem::class, 'internal_parts_inquiry_id')->orderBy('sequence_no');
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_RESPONDED => 'Responded',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
}
