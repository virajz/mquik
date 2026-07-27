<?php

return [
    'label' => 'IPI Report',
    'description' => 'Status and turnaround of internal parts inquiries — by type, status, requester and date. Filterable and exportable.',
    'group' => 'Inventory',
    'icon' => 'document-chart-bar',
    'permissions' => [
        'ipi_report.view',
        'ipi_report.export',
    ],
];
