<?php

namespace App\Modules\DeliveryOrder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrderAttachment extends Model
{
    protected $table = 'delivery_order_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'proforma_copy' => 'Proforma Copy',
            'do_copy' => 'DO Copy',
            'surveyor_consent' => 'Surveyor Consent',
            'customer_consent' => 'Customer Consent',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }
}
