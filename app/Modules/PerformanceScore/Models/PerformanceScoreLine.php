<?php

namespace App\Modules\PerformanceScore\Models;

use App\Modules\SmartSalary\Models\SmartSalary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceScoreLine extends Model
{
    protected $table = 'performance_score_lines';

    protected $guarded = [];

    protected $casts = [
        'max_points' => 'decimal:2',
        'points_awarded' => 'decimal:2',
    ];

    public function performanceScore(): BelongsTo
    {
        return $this->belongsTo(PerformanceScore::class, 'performance_score_id');
    }

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(SmartSalary::class, 'smart_salary_kpi_id');
    }
}
