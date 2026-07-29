<?php

namespace App\Modules\ExcessStockApproval\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcessStockApprovalAttachment extends Model
{
    protected $table = 'excess_stock_approval_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'purchase_invoice_copy' => 'Purchase Invoice Copy',
            'damaged_proof' => 'Damaged Proof',
            'vendor_rejection_proof' => 'Vendor Rejection Proof',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ExcessStockApproval::class, 'excess_stock_approval_id');
    }
}
