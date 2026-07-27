<?php

return [
    'label' => 'Purchase Report',
    'description' => 'Vendor RFQ / purchase inquiries by vendor, type, status and date — for quote comparison and tracking. Filterable and exportable.',
    'group' => 'Inventory',
    'icon' => 'document-chart-bar',
    'permissions' => [
        'purchase_report.view',
        'purchase_report.export',
    ],
];
