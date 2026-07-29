<?php

namespace App\Modules\InvoiceCorrection\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceCorrectionAttachment extends Model
{
    protected $table = 'invoice_correction_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'customer_request_screenshot' => 'Customer Request Screenshot',
            'gst_certificate' => 'GST Certificate',
            'original_invoice_copy' => 'Original Invoice Copy',
            'revised_invoice_copy' => 'Revised Invoice Copy',
        ];
    }

    public function correction(): BelongsTo
    {
        return $this->belongsTo(InvoiceCorrection::class, 'invoice_correction_id');
    }
}
