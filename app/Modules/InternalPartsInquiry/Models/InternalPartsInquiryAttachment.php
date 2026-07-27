<?php

namespace App\Modules\InternalPartsInquiry\Models;

use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file on an inquiry — a typed evidence photo (Before/After/Damage/Fault via
 * PhotoTypeMaster) or a plain document (PDF/image), e.g. a supplier quote.
 */
class InternalPartsInquiryAttachment extends Model
{
    protected $table = 'internal_parts_inquiry_attachments';

    protected $guarded = [];

    protected $casts = ['sequence_no' => 'integer'];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(InternalPartsInquiry::class, 'internal_parts_inquiry_id');
    }

    public function photoType(): BelongsTo
    {
        return $this->belongsTo(PhotoTypeMaster::class, 'photo_type_id');
    }
}
