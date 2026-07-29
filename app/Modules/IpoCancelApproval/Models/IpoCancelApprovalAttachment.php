<?php

namespace App\Modules\IpoCancelApproval\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpoCancelApprovalAttachment extends Model
{
    protected $table = 'ipo_cancel_approval_attachments';

    protected $guarded = [];

    protected $casts = ['sequence_no' => 'integer'];

    public function approval(): BelongsTo
    {
        return $this->belongsTo(IpoCancelApproval::class, 'ipo_cancel_approval_id');
    }
}
