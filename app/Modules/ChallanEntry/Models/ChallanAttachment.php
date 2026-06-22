<?php

namespace App\Modules\ChallanEntry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallanAttachment extends Model
{
    protected $table = 'challan_attachments';

    protected $guarded = [];

    public function challan(): BelongsTo
    {
        return $this->belongsTo(Challan::class, 'challan_id');
    }
}
