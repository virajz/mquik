<?php

use App\Modules\PerformanceScore\Models\PerformanceScore;

return [
    'label' => 'Performance Scores',
    'description' => 'Smart Salary KPI scoring — positive / negative points per employee → achievement %, performance slab and incentive fed to payroll.',
    'group' => 'HR',
    'icon' => 'trophy',
    'permissions' => [
        'performance_score.view',
        'performance_score.create',
        'performance_score.update',
        'performance_score.delete',
    ],
    'searchable' => [
        'model' => PerformanceScore::class,
        'route' => 'performance-score.index',
    ],
];
