<?php

namespace App\Modules\GoodsReturn\Models;

use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReturnItem extends Model
{
    protected $table = 'goods_return_items';

    protected $guarded = [];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_rate' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function goodsReturn(): BelongsTo
    {
        return $this->belongsTo(GoodsReturn::class, 'goods_return_id');
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureMaster::class, 'uom_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(TaxMaster::class, 'tax_id');
    }
}
