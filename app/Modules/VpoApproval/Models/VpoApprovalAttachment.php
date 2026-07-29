<?php

namespace App\Modules\VpoApproval\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpoApprovalAttachment extends Model
{
    protected $table = 'vpo_approval_attachments';

    protected $guarded = [];

    protected $casts = ['sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'vendor_quotation' => 'Vendor Quotation',
            'quote_comparison' => 'Quote Comparison Sheet',
            'approval_notes' => 'Approval Notes',
        ];
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(VpoApproval::class, 'vpo_approval_id');
    }
}
