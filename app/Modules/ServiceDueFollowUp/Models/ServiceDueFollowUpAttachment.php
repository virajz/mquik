<?php

namespace App\Modules\ServiceDueFollowUp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceDueFollowUpAttachment extends Model
{
    protected $table = 'service_due_follow_up_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return ['customer_note' => 'Customer Note', 'follow_up_notes' => 'Follow-Up Notes'];
    }

    public function followUp(): BelongsTo
    {
        return $this->belongsTo(ServiceDueFollowUp::class, 'service_due_follow_up_id');
    }
}
