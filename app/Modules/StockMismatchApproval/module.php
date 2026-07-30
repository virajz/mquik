<?php

use App\Modules\StockMismatchApproval\Models\StockMismatchApproval;

return [
    'label' => 'Stock Mismatch Approval',
    'description' => 'Request / response approval for stock-count variances — variance reason, management response, recount outcome and adjustment method.',
    'group' => 'Inventory',
    'icon' => 'clipboard-document-check',
    'permissions' => [
        'stock_mismatch_approval.view',
        'stock_mismatch_approval.create',
        'stock_mismatch_approval.update',
        'stock_mismatch_approval.delete',
    ],
    'searchable' => [
        'model' => StockMismatchApproval::class,
        'route' => 'stock-mismatch-approval.index',
    ],
];
