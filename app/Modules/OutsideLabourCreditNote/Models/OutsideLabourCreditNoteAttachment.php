<?php

namespace App\Modules\OutsideLabourCreditNote\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutsideLabourCreditNoteAttachment extends Model
{
    protected $table = 'outside_labour_credit_note_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'vendor_bill_copy' => 'Vendor Bill Copy',
            'warranty_card' => 'Warranty Card',
        ];
    }

    public function note(): BelongsTo
    {
        return $this->belongsTo(OutsideLabourCreditNote::class, 'outside_labour_credit_note_id');
    }
}
