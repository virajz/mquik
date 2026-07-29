<?php

namespace App\Modules\AdvancePayment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvancePaymentAttachment extends Model
{
    protected $table = 'advance_payment_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(AdvancePayment::class, 'advance_payment_id');
    }
}
