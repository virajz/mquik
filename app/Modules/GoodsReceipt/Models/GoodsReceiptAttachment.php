<?php

namespace App\Modules\GoodsReceipt\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptAttachment extends Model
{
    protected $table = 'goods_receipt_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'damage_photo' => 'Damage Photo',
            'fault_evidence' => 'Fault Evidence',
            'invoice_copy' => 'Invoice Copy',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }
}
