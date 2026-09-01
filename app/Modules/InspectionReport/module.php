<?php

return [
    'label' => 'Inspection Reports',
    'description' => 'Date-wise and technician-wise inspection turnaround, and the Future Jobs (FA) list the customer has not approved.',
    'group' => 'Inspection',
    'icon' => 'chart-bar',
    'permissions' => [
        'inspection_report.view',
        'inspection_report.export',
    ],
];
