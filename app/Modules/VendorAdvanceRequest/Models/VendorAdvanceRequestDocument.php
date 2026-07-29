<?php

namespace App\Modules\VendorAdvanceRequest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorAdvanceRequestDocument extends Model
{
    protected $table = 'vendor_advance_request_documents';

    protected $guarded = [];

    protected $casts = ['is_provided' => 'boolean', 'sequence_no' => 'integer'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(VendorAdvanceRequest::class, 'vendor_advance_request_id');
    }
}
