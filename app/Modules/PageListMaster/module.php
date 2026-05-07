<?php

return [
    'label' => 'Page List',
    'description' => 'Read-only catalog of every page registered in the system. Useful for permission audits and onboarding new staff.',
    'group' => 'Settings',
    'icon' => 'document-text',
    'permissions' => [
        'page_list_master.view',
    ],
];
