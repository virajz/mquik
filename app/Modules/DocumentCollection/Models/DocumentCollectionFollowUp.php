<?php

namespace App\Modules\DocumentCollection\Models;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One attempt at chasing the documents — when, by whom, channel, and what the customer said. */
class DocumentCollectionFollowUp extends Model
{
    protected $guarded = [];

    protected $casts = [
        'followed_up_at' => 'datetime',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(DocumentCollection::class, 'document_collection_id');
    }

    public function followedUpBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'followed_up_by_id');
    }

    public function mode(): BelongsTo
    {
        return $this->belongsTo(FollowUpModeMaster::class, 'follow_up_mode_id');
    }
}
