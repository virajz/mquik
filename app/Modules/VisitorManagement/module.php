<?php

use App\Modules\VisitorManagement\Models\VisitorVisit;

return [
    'label' => 'Visitor Management (VMS)',
    'description' => 'Reception queue and token system — walk-in / appointment visits, advisor assignment, waiting time and consultation → job-card lifecycle.',
    'group' => 'Workshop',
    'icon' => 'identification',
    'permissions' => [
        'visitor_management.view',
        'visitor_management.create',
        'visitor_management.update',
        'visitor_management.delete',
    ],
    'searchable' => [
        'model' => VisitorVisit::class,
        'route' => 'visitor-management.index',
    ],
];
