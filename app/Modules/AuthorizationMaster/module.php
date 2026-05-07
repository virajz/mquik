<?php

return [
    'label' => 'Roles & Permissions',
    'description' => 'Manage who can access what across the workshop.',
    'group' => 'Settings',
    'icon' => 'shield-check',
    'permissions' => [
        'authorization_master.view',
        'authorization_master.create',
        'authorization_master.update',
        'authorization_master.delete',
        'authorization_master.assign',
    ],
    // No exporter/importer for this module.
    // Not searchable — internal-only screen.
];
