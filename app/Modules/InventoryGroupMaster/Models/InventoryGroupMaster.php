<?php

namespace App\Modules\InventoryGroupMaster\Models;

use App\Concerns\Auditable;
use App\Modules\InventoryGroupMaster\Database\Factories\InventoryGroupMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryGroupMaster extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'inventory_groups';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    protected static function newFactory(): InventoryGroupMasterFactory
    {
        return InventoryGroupMasterFactory::new();
    }
}
