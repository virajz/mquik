<?php

namespace App\Modules\VendorAdvanceRequest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorAdvanceRequestAttachment extends Model
{
    protected $table = 'vendor_advance_request_attachments';

    protected $guarded = [];

    protected $casts = ['sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'cheque_copy' => 'Cheque Copy',
            'utr_screenshot' => 'UTR Screenshot',
            'deposit_slip' => 'Deposit Slip',
            'payment_advice' => 'Payment Advice',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(VendorAdvanceRequest::class, 'vendor_advance_request_id');
    }
}
