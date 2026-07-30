<?php

namespace App\Modules\StockCounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCountAttachment extends Model
{
    protected $table = 'stock_count_attachments';

    protected $guarded = [];

    protected $casts = ['size_bytes' => 'integer', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'physical_count_sheet' => 'Physical Count Sheet',
            'management_approval' => 'Management Approval',
            'damage_photo' => 'Damage Photo',
        ];
    }

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class, 'stock_count_id');
    }
}
