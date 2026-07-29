<?php

namespace App\Modules\GoodsReturnNote\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReturnNoteAttachment extends Model
{
    protected $table = 'goods_return_note_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'vendor_bill_copy' => 'Vendor Bill Copy',
            'warranty_card' => 'Warranty Card',
            'complaint_photo' => 'Complaint Photo',
            'damage_photo' => 'Damage Photo',
            'work_order_copy' => 'Work Order Copy',
            'inspection_report' => 'Inspection Report',
            'front_view' => 'Front View',
            'rear_view' => 'Rear View',
            'left_side' => 'Left Side',
            'right_side' => 'Right Side',
            'fault_evidence' => 'Fault Evidence',
        ];
    }

    public function return(): BelongsTo
    {
        return $this->belongsTo(GoodsReturnNote::class, 'goods_return_note_id');
    }
}
