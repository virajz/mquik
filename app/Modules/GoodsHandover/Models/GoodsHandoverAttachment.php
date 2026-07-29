<?php

namespace App\Modules\GoodsHandover\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsHandoverAttachment extends Model
{
    protected $table = 'goods_handover_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return ['damage_photo' => 'Damage Photo', 'fault_evidence' => 'Fault Evidence'];
    }

    public function handover(): BelongsTo
    {
        return $this->belongsTo(GoodsHandover::class, 'goods_handover_id');
    }
}
