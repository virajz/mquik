<?php

namespace App\Modules\ConsumableApproval\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumableApprovalAttachment extends Model
{
    protected $table = 'consumable_approval_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return ['approval_screenshot' => 'Approval Screenshot'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ConsumableApproval::class, 'consumable_approval_id');
    }
}
