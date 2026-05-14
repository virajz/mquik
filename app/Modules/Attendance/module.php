<?php

return [
    'label' => 'Attendance',
    'description' => 'Per-employee daily punch log with optional selfie + location.',
    'group' => 'HR',
    'icon' => 'finger-print',
    'permissions' => [
        'attendance.view',
        'attendance.create',
        'attendance.update',
        'attendance.delete',
    ],
];
