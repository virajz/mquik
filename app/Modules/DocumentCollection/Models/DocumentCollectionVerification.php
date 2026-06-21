<?php

namespace App\Modules\DocumentCollection\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentCollectionVerification extends Model
{
    protected $table = 'document_collection_verifications';

    protected $guarded = [];

    protected $casts = [
        'is_verified' => 'boolean',
        'sequence_no' => 'integer',
    ];

    public function documentCollection(): BelongsTo
    {
        return $this->belongsTo(DocumentCollection::class, 'document_collection_id');
    }
}
