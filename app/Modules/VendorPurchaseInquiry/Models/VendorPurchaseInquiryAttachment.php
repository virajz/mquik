<?php

namespace App\Modules\VendorPurchaseInquiry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A document on an RFQ — the RFQ document sent, the vendor's quotation, or an
 * internal approval note. PDF or image.
 */
class VendorPurchaseInquiryAttachment extends Model
{
    protected $table = 'vendor_purchase_inquiry_attachments';

    protected $guarded = [];

    protected $casts = ['sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'rfq_document' => 'RFQ Document',
            'vendor_quotation' => 'Vendor Quotation',
            'approval_note' => 'Approval Note',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(VendorPurchaseInquiry::class, 'vendor_purchase_inquiry_id');
    }
}
