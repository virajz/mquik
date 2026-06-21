<?php

namespace App\Modules\JobStageMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\JobStageMaster\Database\Factories\JobStageMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobStageMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'job_stages';

    protected $guarded = [];

    public const TRACK_REGULAR = 'regular';

    public const TRACK_INSURANCE = 'insurance';

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static array $searchableFields = ['name', 'code', 'track'];

    protected static function newFactory(): JobStageMasterFactory
    {
        return JobStageMasterFactory::new();
    }

    /**
     * @return array<string, string>
     */
    public static function tracks(): array
    {
        return [
            self::TRACK_REGULAR => 'Regular',
            self::TRACK_INSURANCE => 'Insurance',
        ];
    }
}
