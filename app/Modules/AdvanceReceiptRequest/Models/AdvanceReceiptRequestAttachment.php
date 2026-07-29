<?php

namespace App\Modules\AdvanceReceiptRequest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A supporting document on an advance request — PDF or image.
 */
class AdvanceReceiptRequestAttachment extends Model
{
    protected $table = 'advance_receipt_request_attachments';

    protected $guarded = [];

    protected $casts = ['sequence_no' => 'integer'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(AdvanceReceiptRequest::class, 'advance_receipt_request_id');
    }
}
