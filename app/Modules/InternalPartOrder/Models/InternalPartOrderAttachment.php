<?php

namespace App\Modules\InternalPartOrder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalPartOrderAttachment extends Model
{
    protected $table = 'internal_part_order_attachments';

    protected $guarded = [];

    public function order(): BelongsTo
    {
        return $this->belongsTo(InternalPartOrder::class, 'internal_part_order_id');
    }
}
