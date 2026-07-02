<?php

namespace App\Modules\GoodsReturn\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReturnAttachment extends Model
{
    protected $table = 'goods_return_attachments';

    protected $guarded = [];

    public function goodsReturn(): BelongsTo
    {
        return $this->belongsTo(GoodsReturn::class, 'goods_return_id');
    }
}
