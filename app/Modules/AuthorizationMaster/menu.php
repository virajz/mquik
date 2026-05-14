<?php

return [
    [
        'mode' => 'setup',
        'group' => 'Settings',
        'label' => 'Roles & Permissions',
        'icon' => 'shield-check',
        'route' => 'authorization-master.index',
        'permission' => 'authorization_master.view',
        'order' => 10,
    ],
    [
        'mode' => 'setup',
        'group' => 'Settings',
        'label' => 'Users',
        'icon' => 'users',
        'route' => 'authorization-master.users',
        'permission' => 'authorization_master.view',
        'order' => 20,
    ],
];
