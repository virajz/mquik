<?php

return [
    'label' => 'Leave Management',
    'description' => 'Per-employee leave requests with approval workflow.',
    'group' => 'HR',
    'icon' => 'calendar-days',
    'permissions' => [
        'leave_management.view',
        'leave_management.create',
        'leave_management.update',
        'leave_management.delete',
    ],
];
