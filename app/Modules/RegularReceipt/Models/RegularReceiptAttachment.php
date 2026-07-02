<?php

namespace App\Modules\RegularReceipt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegularReceiptAttachment extends Model
{
    protected $table = 'regular_receipt_attachments';

    protected $guarded = [];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(RegularReceipt::class, 'regular_receipt_id');
    }
}
