<?php

return [
    'label' => 'Recommendation Categories',
    'description' => 'Categories and sub categories used to file recommendation wording for inspection checkpoints.',
    'group' => 'Masters',
    'icon' => 'folder-open',
    'permissions' => [
        'recommendation_category_master.view',
        'recommendation_category_master.create',
        'recommendation_category_master.update',
        'recommendation_category_master.delete',
    ],
];
