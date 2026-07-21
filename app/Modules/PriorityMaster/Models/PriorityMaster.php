<?php

namespace App\Modules\PriorityMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\PriorityMaster\Database\Factories\PriorityMasterFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriorityMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    /** Workshop-job urgency — appointments, inspection orders. */
    public const APPLIES_WORKSHOP = 'workshop';

    /** Parts-supply urgency — internal part orders. */
    public const APPLIES_PARTS = 'parts';

    public const APPLIES_BOTH = 'both';

    protected $table = 'priorities';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): PriorityMasterFactory
    {
        return PriorityMasterFactory::new();
    }

    /**
     * @return array<string, string>
     */
    public static function scopes(): array
    {
        return [
            self::APPLIES_WORKSHOP => 'Workshop jobs',
            self::APPLIES_PARTS => 'Parts orders',
            self::APPLIES_BOTH => 'Both',
        ];
    }

    /**
     * Active priorities a given module may offer, in severity order. Pass
     * `workshop` or `parts`; values tagged `both` always come through.
     *
     * @return Builder<PriorityMaster>
     */
    public static function forScope(string $scope): Builder
    {
        return static::query()
            ->where('is_active', true)
            ->whereIn('applies_to', [$scope, self::APPLIES_BOTH])
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}
