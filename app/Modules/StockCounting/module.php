<?php

use App\Modules\StockCounting\Models\StockCount;

return [
    'label' => 'Stock Counting',
    'description' => 'Physical & system stock verification: counting sessions, per-spare variance (system vs physical) with reasons, and count-sheet / approval attachments (SC- series).',
    'group' => 'Inventory',
    'icon' => 'chart-bar',
    'permissions' => [
        'stock_counting.view',
        'stock_counting.create',
        'stock_counting.update',
        'stock_counting.delete',
    ],
    'searchable' => [
        'model' => StockCount::class,
        'route' => 'stock-counting.index',
    ],
];
