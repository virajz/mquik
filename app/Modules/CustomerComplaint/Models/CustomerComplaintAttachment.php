<?php

namespace App\Modules\CustomerComplaint\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerComplaintAttachment extends Model
{
    protected $table = 'customer_complaint_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'complaint_copy' => 'Complaint Copy',
            'complaint_video' => 'Complaint Video',
            'voice_recording' => 'Voice Recording',
            'investigation_report' => 'Investigation Report',
            'resolution_copy' => 'Resolution Copy',
        ];
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(CustomerComplaint::class, 'customer_complaint_id');
    }
}
