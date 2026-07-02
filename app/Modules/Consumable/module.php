<?php

use App\Modules\Consumable\Models\Consumable;

return [
    'label' => 'Consumables',
    'description' => 'Track consumable usage & loss by category, with approval, and auto-deduct spare stock.',
    'group' => 'Inventory',
    'icon' => 'beaker',
    'permissions' => [
        'consumable.view',
        'consumable.create',
        'consumable.update',
        'consumable.delete',
    ],
    'searchable' => [
        'model' => Consumable::class,
        'route' => 'consumable.index',
    ],
];
