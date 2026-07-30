<?php

namespace App\Modules\VisitorManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorVisitAttachment extends Model
{
    protected $table = 'visitor_visit_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'token_slip' => 'Token Slip',
            'customer_note' => 'Customer Note',
            'visit_note' => 'Visit Note',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(VisitorVisit::class, 'visitor_visit_id');
    }
}
