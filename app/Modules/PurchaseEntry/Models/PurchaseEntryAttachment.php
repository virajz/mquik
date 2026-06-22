<?php

namespace App\Modules\PurchaseEntry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseEntryAttachment extends Model
{
    protected $table = 'purchase_entry_attachments';

    protected $guarded = [];

    public function purchaseEntry(): BelongsTo
    {
        return $this->belongsTo(PurchaseEntry::class, 'purchase_entry_id');
    }
}
