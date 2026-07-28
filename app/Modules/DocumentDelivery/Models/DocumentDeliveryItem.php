<?php

namespace App\Modules\DocumentDelivery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One document on a delivery's checklist, with whether it was handed over.
 */
class DocumentDeliveryItem extends Model
{
    protected $table = 'document_delivery_items';

    protected $guarded = [];

    protected $casts = [
        'is_delivered' => 'boolean',
        'sequence_no' => 'integer',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(DocumentDelivery::class, 'document_delivery_id');
    }
}
