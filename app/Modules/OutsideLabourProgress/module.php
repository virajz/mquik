<?php

return [
    'label' => 'Outside Labour Progress',
    'description' => 'OLR — track outside work progress, completion and total hours consumed across outside labour orders.',
    'group' => 'Workshop',
    'icon' => 'chart-bar',
    'permissions' => [
        'outside_labour_progress.view',
        'outside_labour_progress.export',
    ],
];
