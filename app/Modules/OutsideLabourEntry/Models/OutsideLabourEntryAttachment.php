<?php

namespace App\Modules\OutsideLabourEntry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutsideLabourEntryAttachment extends Model
{
    protected $table = 'outside_labour_entry_attachments';

    protected $guarded = [];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(OutsideLabourEntry::class, 'outside_labour_entry_id');
    }
}
