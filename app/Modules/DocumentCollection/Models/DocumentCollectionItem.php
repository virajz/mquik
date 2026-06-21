<?php

namespace App\Modules\DocumentCollection\Models;

use App\Modules\DocumentRejectionReasonMaster\Models\DocumentRejectionReasonMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentCollectionItem extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'document_collection_items';

    protected $guarded = [];

    protected $casts = [
        'is_required' => 'boolean',
        'size_bytes' => 'integer',
        'sequence_no' => 'integer',
    ];

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_RECEIVED => 'Received',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    public function documentCollection(): BelongsTo
    {
        return $this->belongsTo(DocumentCollection::class, 'document_collection_id');
    }

    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(DocumentRejectionReasonMaster::class, 'rejection_reason_id');
    }
}
