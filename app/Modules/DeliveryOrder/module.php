<?php

use App\Modules\DeliveryOrder\Models\DeliveryOrder;

return [
    'label' => 'Delivery Order (DO)',
    'description' => 'Record the insurance Delivery Order against a claim before vehicle delivery, with proforma-vs-DO amount comparison.',
    'group' => 'Insurance',
    'icon' => 'document-check',
    'permissions' => [
        'delivery_order.view',
        'delivery_order.create',
        'delivery_order.update',
        'delivery_order.delete',
    ],
    'searchable' => [
        'model' => DeliveryOrder::class,
        'route' => 'delivery-order.index',
    ],
];
