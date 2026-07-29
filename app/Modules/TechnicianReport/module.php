<?php

return [
    'label' => 'Technician Report',
    'description' => 'FWR — technician working time, item-wise hours, pause time and net TAT across final work orders.',
    'group' => 'Workshop',
    'icon' => 'user-group',
    'permissions' => [
        'technician_report.view',
        'technician_report.export',
    ],
];
