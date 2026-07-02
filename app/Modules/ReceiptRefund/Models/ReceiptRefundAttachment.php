<?php

namespace App\Modules\ReceiptRefund\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptRefundAttachment extends Model
{
    protected $table = 'receipt_refund_attachments';

    protected $guarded = [];

    public function refund(): BelongsTo
    {
        return $this->belongsTo(ReceiptRefund::class, 'receipt_refund_id');
    }
}
