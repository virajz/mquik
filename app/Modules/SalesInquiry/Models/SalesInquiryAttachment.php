<?php

namespace App\Modules\SalesInquiry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInquiryAttachment extends Model
{
    protected $table = 'sales_inquiry_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'vin_photo' => 'VIN Photo',
            'vehicle_photo' => 'Vehicle Photos',
            'voice_recording' => 'Voice Recording',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(SalesInquiry::class, 'sales_inquiry_id');
    }
}
