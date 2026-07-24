<?php

use App\Modules\TyreReport\Models\TyreReport;

return [
    'label' => 'Tyre Reports',
    'description' => 'Per-visit five-wheel tyre inspection — size, pressure, tread depth, condition and replace recommendations.',
    'group' => 'Workshop',
    'icon' => 'lifebuoy',
    'permissions' => [
        'tyre_report.view',
        'tyre_report.create',
        'tyre_report.update',
        'tyre_report.delete',
    ],
    'searchable' => [
        'model' => TyreReport::class,
        'route' => 'tyre-report.index',
    ],
];
