<?php

namespace App\Modules\VendorMaster\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorAttachment extends Model
{
    protected $table = 'vendor_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'gst_certificate' => 'GST Certificate',
            'pan_card' => 'PAN Card',
            'msme_certificate' => 'MSME Certificate',
            'cancelled_cheque' => 'Cancelled Cheque',
            'bank_passbook' => 'Bank Passbook',
            'tcs_signed_copy' => 'T&Cs Signed Copy',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }
}
