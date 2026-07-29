<?php

namespace App\Modules\GatePassApproval\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatePassApprovalAttachment extends Model
{
    protected $table = 'gate_pass_approval_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'customer_request_letter' => 'Customer Request Letter',
            'customer_request_form' => 'Customer Request Form',
            'approval_note' => 'Approval Note',
            'payment_commitment' => 'Payment Commitment',
            'post_dated_cheque' => 'Post Dated Cheque',
        ];
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(GatePassApproval::class, 'gate_pass_approval_id');
    }
}
