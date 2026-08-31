<?php

namespace App\Modules\FinalInspection\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\DigitalInspection\Models\DigitalInspection;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FinalInspection\Database\Factories\FinalInspectionFactory;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\ReworkReasonMaster\Models\ReworkReasonMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinalInspection extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'final_inspections';

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    protected static array $searchableFields = ['inspection_no', 'summary_notes', 'jobCard.job_card_no'];

    protected static function newFactory(): FinalInspectionFactory
    {
        return FinalInspectionFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->inspection_no === null) {
                $row->forceFill([
                    'inspection_no' => 'FI-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function digitalInspection(): BelongsTo
    {
        return $this->belongsTo(DigitalInspection::class, 'digital_inspection_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(InspectionTemplateMaster::class, 'inspection_template_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'inspector_id');
    }

    public function reworkReason(): BelongsTo
    {
        return $this->belongsTo(ReworkReasonMaster::class, 'rework_reason_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FinalInspectionItem::class, 'final_inspection_id')->orderBy('sequence_no');
    }

    public function pauses(): HasMany
    {
        return $this->hasMany(FinalInspectionPause::class, 'final_inspection_id')->orderBy('paused_at');
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'on_hold' => 'On Hold',
            'completed' => 'Completed',
            'rework' => 'Rework',
            'cancelled' => 'Cancelled',
            'not_applicable' => 'Not Applicable',
        ];
    }

    /** @return array<string, string> */
    public static function completionTypes(): array
    {
        return [
            'fully' => 'Fully Completed',
            'partially' => 'Partially Completed',
            'deferred' => 'Deferred',
        ];
    }

    /** @return array<string, string> */
    public static function recommendationTypes(): array
    {
        return [
            'new_issue' => 'New Issue Found',
            'additional_parts' => 'Additional Parts Required',
            'additional_labour' => 'Additional Labour Required',
        ];
    }

    /** Per-item result — reuses the Digital Inspection action set (IA / FA / NA). */
    public static function results(): array
    {
        return DigitalInspection::outcomes();
    }

    /** Including the retired condition words, so historic rows still render. */
    public static function allResults(): array
    {
        return DigitalInspection::allOutcomes();
    }

    /** Per-item recommendation — reuses the Digital Inspection set. */
    public static function itemRecommendations(): array
    {
        return DigitalInspection::recommendations();
    }

    /** Including the retired values, so historic rows still render. */
    public static function allItemRecommendations(): array
    {
        return DigitalInspection::allRecommendations();
    }

    /** Per-item severity — reuses the Digital Inspection set. */
    public static function severities(): array
    {
        return DigitalInspection::severities();
    }
}
