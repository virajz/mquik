<?php

namespace App\Modules\OutsideLabourBill\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutsideLabourBillAttachment extends Model
{
    protected $table = 'outside_labour_bill_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'original_invoice' => 'Original Invoice Copy',
            'duplicate_invoice' => 'Duplicate Invoice Copy',
            'whatsapp_screenshot' => 'WhatsApp Screenshot',
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(OutsideLabourBill::class, 'outside_labour_bill_id');
    }
}
