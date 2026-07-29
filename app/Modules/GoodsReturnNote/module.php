<?php

use App\Modules\GoodsReturnNote\Models\GoodsReturnNote;

return [
    'label' => 'Goods Return Note',
    'description' => 'Return excess, incorrect, defective or warranty parts / labour to a vendor — single parts + labour return with rework, replacement or CN/DN settlement.',
    'group' => 'Inventory',
    'icon' => 'arrow-uturn-left',
    'permissions' => [
        'goods_return_note.view',
        'goods_return_note.create',
        'goods_return_note.update',
        'goods_return_note.delete',
    ],
    'searchable' => [
        'model' => GoodsReturnNote::class,
        'route' => 'goods-return-note.index',
    ],
];
