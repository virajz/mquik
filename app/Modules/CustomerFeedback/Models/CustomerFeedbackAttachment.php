<?php

namespace App\Modules\CustomerFeedback\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerFeedbackAttachment extends Model
{
    protected $table = 'customer_feedback_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return ['feedback_screenshot' => 'Feedback Screenshot'];
    }

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(CustomerFeedback::class, 'customer_feedback_id');
    }
}
