<?php

return [
    [
        // Configuration a workshop tunes once, not day-to-day operations.
        'mode' => 'setup',
        'group' => 'Workshop',
        'label' => 'Service Intervals',
        'icon' => 'clock',
        'route' => 'service-interval-master.index',
        'permission' => 'service_interval_master.view',
        'order' => 40,
    ],
];
