<?php

namespace App\Modules\InternalPartsInquiry\Models;

use App\Modules\SpareMaster\Models\SpareMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalPartsInquiryItem extends Model
{
    protected $table = 'internal_parts_inquiry_items';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'needed_by_date' => 'datetime',
    ];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(InternalPartsInquiry::class, 'internal_parts_inquiry_id');
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }
}
