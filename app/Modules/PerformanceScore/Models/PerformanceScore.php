<?php

namespace App\Modules\PerformanceScore\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\PerformanceScore\Database\Factories\PerformanceScoreFactory;
use App\Modules\PerformanceSlabMaster\Models\PerformanceSlabMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceScore extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'performance_scores';

    protected $guarded = [];

    protected $casts = [
        'period_year' => 'integer',
        'period_month' => 'integer',
        'total_positive' => 'decimal:2',
        'total_negative' => 'decimal:2',
        'net_points' => 'decimal:2',
        'max_points' => 'decimal:2',
        'achievement_percent' => 'decimal:2',
        'incentive_amount' => 'decimal:2',
    ];

    protected static array $searchableFields = ['notes'];

    protected static function newFactory(): PerformanceScoreFactory
    {
        return PerformanceScoreFactory::new();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
    }

    public function performanceSlab(): BelongsTo
    {
        return $this->belongsTo(PerformanceSlabMaster::class, 'performance_slab_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PerformanceScoreLine::class, 'performance_score_id')->orderBy('sequence_no');
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'finalized' => 'Finalized',
        ];
    }

    /** @return array<int, string> */
    public static function months(): array
    {
        return [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
    }

    public function periodLabel(): string
    {
        return (self::months()[$this->period_month] ?? '?').' '.$this->period_year;
    }
}
