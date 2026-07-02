<?php

use App\Modules\GoodsReturn\Models\GoodsReturn;

return [
    'label' => 'Goods Returns',
    'description' => 'Vendor credit / debit notes for returned parts — reduces stock (RTS settlement).',
    'group' => 'Purchase',
    'icon' => 'receipt-refund',
    'permissions' => [
        'goods_return.view',
        'goods_return.create',
        'goods_return.update',
        'goods_return.delete',
    ],
    'searchable' => [
        'model' => GoodsReturn::class,
        'route' => 'goods-return.index',
    ],
];
