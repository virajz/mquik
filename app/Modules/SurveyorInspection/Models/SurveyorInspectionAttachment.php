<?php

namespace App\Modules\SurveyorInspection\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file on a surveyor inspection — an approval note or survey photo (PDF/image).
 */
class SurveyorInspectionAttachment extends Model
{
    protected $table = 'surveyor_inspection_attachments';

    protected $guarded = [];

    protected $casts = ['sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'approval_note' => 'Approval Note',
            'survey_photo' => 'Survey Photo',
            'other' => 'Other',
        ];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(SurveyorInspection::class, 'surveyor_inspection_id');
    }
}
