<?php

use App\Modules\SalesEstimate\Models\SalesEstimate;

return [
    'label' => 'Sales Estimates',
    'description' => 'Regular & insurance estimates — parts/labour lines, discounts, tax, approval workflow, revisions and insurance pass %.',
    'group' => 'Sales',
    'icon' => 'calculator',
    'permissions' => [
        'sales_estimate.view',
        'sales_estimate.create',
        'sales_estimate.update',
        'sales_estimate.delete',
    ],
    'searchable' => [
        'model' => SalesEstimate::class,
        'route' => 'sales-estimate.index',
    ],
];
