<?php

return [
    'label' => 'Outside Labour Status Report',
    'description' => 'Status and turnaround of outside labour inquiries — by type, vendor, status and date. Filterable and exportable.',
    'group' => 'Workshop',
    'icon' => 'document-chart-bar',
    'permissions' => [
        'outside_labour_status_report.view',
        'outside_labour_status_report.export',
    ],
];
