<?php

namespace App\Modules\PerformanceSlabMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\PerformanceSlabMaster\Database\Factories\PerformanceSlabMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceSlabMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'performance_slabs';

    protected $guarded = [];

    protected $casts = [
        'min_percent' => 'decimal:2',
        'max_percent' => 'decimal:2',
        'incentive_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): PerformanceSlabMasterFactory
    {
        return PerformanceSlabMasterFactory::new();
    }
}
