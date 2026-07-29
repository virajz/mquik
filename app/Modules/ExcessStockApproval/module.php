<?php

use App\Modules\ExcessStockApproval\Models\ExcessStockApproval;

return [
    'label' => 'Excess Stock Approval',
    'description' => 'Request admin approval to return or write off excess / dead stock, with excess and dead-stock value tracking.',
    'group' => 'Inventory',
    'icon' => 'archive-box-x-mark',
    'permissions' => [
        'excess_stock_approval.view',
        'excess_stock_approval.create',
        'excess_stock_approval.update',
        'excess_stock_approval.delete',
    ],
    'searchable' => [
        'model' => ExcessStockApproval::class,
        'route' => 'excess-stock-approval.index',
    ],
];
