<?php

namespace App\Modules\AdvanceReceipt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file on an advance receipt — cheque copy, UTR screenshot, deposit slip or
 * payment advice (PDF/image).
 */
class AdvanceReceiptAttachment extends Model
{
    protected $table = 'advance_receipt_attachments';

    protected $guarded = [];

    protected $casts = ['sequence_no' => 'integer'];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(AdvanceReceipt::class, 'advance_receipt_id');
    }
}
