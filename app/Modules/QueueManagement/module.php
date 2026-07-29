<?php

use App\Modules\QueueManagement\Models\ServiceQueue;

return [
    'label' => 'Queue Management',
    'description' => 'Live service queue / display board — car wash, alignment, PDI, detailing; FIFO/priority ordering, waiting/service/TAT timing.',
    'group' => 'Workshop',
    'icon' => 'queue-list',
    'permissions' => [
        'queue_management.view',
        'queue_management.create',
        'queue_management.update',
        'queue_management.delete',
    ],
    'searchable' => [
        'model' => ServiceQueue::class,
        'route' => 'queue-management.index',
    ],
];
