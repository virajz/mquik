<?php

use App\Modules\GoodsHandover\Models\GoodsHandover;

return [
    'label' => 'Goods Handover / Parts Return',
    'description' => 'Hand received parts to a technician and capture technician parts returns (excess / wrong / not required / defective).',
    'group' => 'Inventory',
    'icon' => 'arrows-right-left',
    'permissions' => [
        'goods_handover.view',
        'goods_handover.create',
        'goods_handover.update',
        'goods_handover.delete',
    ],
    'searchable' => [
        'model' => GoodsHandover::class,
        'route' => 'goods-handover.index',
    ],
];
