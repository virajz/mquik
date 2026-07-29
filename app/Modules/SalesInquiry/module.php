<?php

use App\Modules\SalesInquiry\Models\SalesInquiry;

return [
    'label' => 'Sales Inquiries',
    'description' => 'Capture a service / parts inquiry from any channel, assign, follow up and track to conversion or loss.',
    'group' => 'CRM',
    'icon' => 'phone-arrow-down-left',
    'permissions' => [
        'sales_inquiry.view',
        'sales_inquiry.create',
        'sales_inquiry.update',
        'sales_inquiry.delete',
    ],
    'searchable' => [
        'model' => SalesInquiry::class,
        'route' => 'sales-inquiry.index',
    ],
];
