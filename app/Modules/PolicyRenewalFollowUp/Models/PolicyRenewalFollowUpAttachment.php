<?php

namespace App\Modules\PolicyRenewalFollowUp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyRenewalFollowUpAttachment extends Model
{
    protected $table = 'policy_renewal_follow_up_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'rc' => 'RC',
            'aadhar' => 'Aadhar',
            'pan' => 'PAN',
            'previous_policy' => 'Previous Policy Copy',
            'insurance_quote' => 'Insurance Quote',
            'renewed_policy' => 'Renewed Policy',
            'payment_receipt' => 'Payment Receipt',
        ];
    }

    public function followUp(): BelongsTo
    {
        return $this->belongsTo(PolicyRenewalFollowUp::class, 'policy_renewal_follow_up_id');
    }
}
