<?php

namespace App\Modules\VpoCancelRequest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpoCancelRequestAttachment extends Model
{
    protected $table = 'vpo_cancel_request_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'dispatch_challan' => 'Dispatch Challan',
            'courier_receipt' => 'Courier Receipt',
            'transport_receipt' => 'Transport Receipt',
            'invoice_copy' => 'Invoice Copy',
            'refund_receipt' => 'Refund Receipt',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(VpoCancelRequest::class, 'vpo_cancel_request_id');
    }
}
