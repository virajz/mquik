<?php

return [
    [
        'mode' => 'operations',
        'group' => 'Inspection',
        'label' => 'VIO History',
        'icon' => 'clock',
        'route' => 'inspection-order-history.index',
        'permission' => 'inspection_order_history.view',
        'order' => 21,
    ],
    [
        'mode' => 'operations',
        'group' => 'Inspection',
        'label' => 'Technician TAT',
        'icon' => 'chart-bar',
        'route' => 'inspection-order-tat.index',
        'permission' => 'inspection_order_history.view',
        'order' => 22,
    ],
];
