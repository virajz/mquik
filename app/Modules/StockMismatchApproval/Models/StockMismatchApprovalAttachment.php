<?php

namespace App\Modules\StockMismatchApproval\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMismatchApprovalAttachment extends Model
{
    protected $table = 'stock_mismatch_approval_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'mismatch_note' => 'Mismatch Note',
            'investigation_note' => 'Investigation Note',
            'approval_note' => 'Approval Note',
        ];
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(StockMismatchApproval::class, 'stock_mismatch_approval_id');
    }
}
