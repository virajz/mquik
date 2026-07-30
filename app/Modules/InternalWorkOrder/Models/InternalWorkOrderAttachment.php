<?php

namespace App\Modules\InternalWorkOrder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalWorkOrderAttachment extends Model
{
    protected $table = 'internal_work_order_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'image' => 'Image',
            'video' => 'Video',
            'pdf' => 'PDF',
            'screenshot' => 'Screenshot',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(InternalWorkOrder::class, 'internal_work_order_id');
    }
}
