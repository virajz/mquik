<?php

use App\Modules\GoodsReceipt\Models\GoodsReceipt;

return [
    'label' => 'Goods Receive & Verification',
    'description' => 'Receive vendor parts (against a PO or direct), verify each line, QC, per-line approval and storage allocation.',
    'group' => 'Inventory',
    'icon' => 'inbox-arrow-down',
    'permissions' => [
        'goods_receipt.view',
        'goods_receipt.create',
        'goods_receipt.update',
        'goods_receipt.delete',
    ],
    'searchable' => [
        'model' => GoodsReceipt::class,
        'route' => 'goods-receipt.index',
    ],
];
