<?php

return [
    'label' => 'Audit Log',
    'description' => 'Activity history — who created, modified, or deleted records and when.',
    'group' => 'Settings',
    'icon' => 'clock',
    'permissions' => [
        'audit_log_master.view',
    ],
];
