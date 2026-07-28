<?php

use App\Modules\SalesEstimateApproval\Models\SalesEstimateApproval;

return [
    'label' => 'Sales Estimate Approval',
    'description' => 'Customer / advisor / insurer approval of a sales estimate, line-by-line, with depreciation and an 8-state lifecycle.',
    'group' => 'Insurance',
    'icon' => 'clipboard-document-check',
    'permissions' => [
        'sales_estimate_approval.view',
        'sales_estimate_approval.create',
        'sales_estimate_approval.update',
        'sales_estimate_approval.delete',
    ],
    'searchable' => [
        'model' => SalesEstimateApproval::class,
        'route' => 'sales-estimate-approval.index',
    ],
];
