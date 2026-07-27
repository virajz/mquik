<?php

namespace App\Modules\SurveyorInspection\Models;

use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One spare/labour line the surveyor assessed, with their per-line decision.
 */
class SurveyorInspectionItem extends Model
{
    protected $table = 'surveyor_inspection_items';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'sequence_no' => 'integer',
    ];

    /** @return array<string, string> */
    public static function lineApprovals(): array
    {
        return [
            'repair' => 'Repair',
            'replace' => 'Replace',
            'remove_refit' => 'Remove & Refit',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'pending' => 'Approval Pending',
        ];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(SurveyorInspection::class, 'surveyor_inspection_id');
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function labour(): BelongsTo
    {
        return $this->belongsTo(LabourMaster::class, 'labour_id');
    }
}
