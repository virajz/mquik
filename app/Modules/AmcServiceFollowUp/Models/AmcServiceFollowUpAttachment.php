<?php

namespace App\Modules\AmcServiceFollowUp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmcServiceFollowUpAttachment extends Model
{
    protected $table = 'amc_service_follow_up_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'service_schedule' => 'Service Schedule',
            'customer_note' => 'Customer Note',
            'follow_up_notes' => 'Follow-Up Notes',
        ];
    }

    public function followUp(): BelongsTo
    {
        return $this->belongsTo(AmcServiceFollowUp::class, 'amc_service_follow_up_id');
    }
}
