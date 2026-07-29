<?php

namespace App\Modules\ProformaApproval\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProformaApprovalAttachment extends Model
{
    protected $table = 'proforma_approval_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'proforma_format_1' => 'Proforma Print (Format-1 · Advisor)',
            'proforma_format_2' => 'Proforma Print (Format-2 · Admin)',
            'approval_screenshot' => 'Approval Screenshot',
        ];
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(ProformaApproval::class, 'proforma_approval_id');
    }
}
