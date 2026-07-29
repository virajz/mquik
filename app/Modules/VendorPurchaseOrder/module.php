<?php

use App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrder;

return [
    'label' => 'Vendor Purchase Orders',
    'description' => 'VPO — the confirmed order placed on a vendor: ordered lines, charges, delivery terms, acknowledgement and dispatch tracking.',
    'group' => 'Inventory',
    'icon' => 'shopping-cart',
    'permissions' => [
        'vendor_purchase_order.view',
        'vendor_purchase_order.create',
        'vendor_purchase_order.update',
        'vendor_purchase_order.delete',
    ],
    'searchable' => [
        'model' => VendorPurchaseOrder::class,
        'route' => 'vendor-purchase-order.index',
    ],
];
