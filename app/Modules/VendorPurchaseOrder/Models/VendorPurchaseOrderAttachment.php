<?php

namespace App\Modules\VendorPurchaseOrder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A document on a VPO — the PO copy, the vendor's dispatch copy, the invoice
 * copy, or photo evidence of the goods. PDF or image.
 */
class VendorPurchaseOrderAttachment extends Model
{
    protected $table = 'vendor_purchase_order_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'po_copy' => 'PO Copy',
            'dispatch_copy' => 'Dispatch Copy',
            'invoice_copy' => 'Invoice Copy',
            'photo_evidence' => 'Photo Evidence',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(VendorPurchaseOrder::class, 'vendor_purchase_order_id');
    }
}
