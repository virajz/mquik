<?php

return [
    'label' => 'VIO History',
    'description' => 'Historical log of vehicle inspection orders — technician, bay, status, and turnaround. Filterable and exportable.',
    'group' => 'Inspection',
    'icon' => 'clock',
    'permissions' => [
        'inspection_order_history.view',
        'inspection_order_history.export',
    ],
];
