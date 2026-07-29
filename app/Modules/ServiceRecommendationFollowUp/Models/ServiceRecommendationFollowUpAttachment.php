<?php

namespace App\Modules\ServiceRecommendationFollowUp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRecommendationFollowUpAttachment extends Model
{
    protected $table = 'service_recommendation_follow_up_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return ['customer_note' => 'Customer Note', 'follow_up_notes' => 'Follow-Up Notes'];
    }

    public function followUp(): BelongsTo
    {
        return $this->belongsTo(ServiceRecommendationFollowUp::class, 'service_recommendation_follow_up_id');
    }
}
