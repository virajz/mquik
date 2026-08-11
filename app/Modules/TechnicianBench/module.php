<?php

return [
    'label' => 'Technician Bench',
    'description' => 'Floor screen: a technician picks themselves, sees their assigned task lines across work orders, and starts / pauses / completes each one.',
    'group' => 'Workshop',
    'icon' => 'play-circle',
    'permissions' => [
        'technician_bench.view',
        'technician_bench.update',
    ],
];
