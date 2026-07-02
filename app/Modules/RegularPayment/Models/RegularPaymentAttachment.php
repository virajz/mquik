<?php

namespace App\Modules\RegularPayment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegularPaymentAttachment extends Model
{
    protected $table = 'regular_payment_attachments';

    protected $guarded = [];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(RegularPayment::class, 'regular_payment_id');
    }
}
