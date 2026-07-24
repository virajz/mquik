<?php

return [
    'label' => 'Stock Report',
    'description' => 'On-hand quantity, value and reorder status per spare — filterable and exportable.',
    'group' => 'Inventory',
    'icon' => 'clipboard-document-list',
    'permissions' => [
        'stock_report.view',
        'stock_report.export',
    ],
];
