<?php

namespace App\Modules\IncentivePolicyMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\IncentivePolicyMaster\Database\Factories\IncentivePolicyMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncentivePolicyMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'incentive_policies';

    protected $guarded = [];

    protected $casts = [
        'rate_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): IncentivePolicyMasterFactory
    {
        return IncentivePolicyMasterFactory::new();
    }

    /** @return array<string, string> */
    public static function bases(): array
    {
        return [
            'labour_sales' => 'Labour Sales',
            'parts_sales' => 'Parts Sales',
            'customer_satisfaction' => 'Customer Satisfaction',
            'efficiency' => 'Efficiency',
        ];
    }
}
