<?php

namespace App\Modules\OutsideLabourInquiry\Models;

use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file on an inquiry — either a typed evidence photo (Before/After/Front/
 * Rear/Damage/Fault via PhotoTypeMaster) or a plain document (PDF/image), e.g.
 * the vendor's quotation.
 */
class OutsideLabourInquiryAttachment extends Model
{
    protected $table = 'outside_labour_inquiry_attachments';

    protected $guarded = [];

    protected $casts = ['sequence_no' => 'integer'];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(OutsideLabourInquiry::class, 'outside_labour_inquiry_id');
    }

    public function photoType(): BelongsTo
    {
        return $this->belongsTo(PhotoTypeMaster::class, 'photo_type_id');
    }
}
