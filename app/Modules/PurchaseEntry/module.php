<?php

use App\Modules\PurchaseEntry\Models\PurchaseEntry;

return [
    'label' => 'Purchase Entries',
    'description' => 'Full purchase invoice entry — receives parts into stock, maps HSN/tax, with discounts, charges & attachments.',
    'group' => 'Purchase',
    'icon' => 'shopping-cart',
    'permissions' => [
        'purchase_entry.view',
        'purchase_entry.create',
        'purchase_entry.update',
        'purchase_entry.delete',
    ],
    'searchable' => [
        'model' => PurchaseEntry::class,
        'route' => 'purchase-entry.index',
    ],
];
