<?php

use App\Modules\PickupDrop\Models\PickupDrop;

return [
    'label' => 'Pickup / Drop',
    'description' => 'Vehicle pickup and drop scheduling — driver/vendor assignment and status tracking.',
    'group' => 'Workshop',
    'icon' => 'truck',
    'permissions' => [
        'pickup_drop.view',
        'pickup_drop.create',
        'pickup_drop.update',
        'pickup_drop.delete',
    ],
    'searchable' => [
        'model' => PickupDrop::class,
        'route' => 'pickup-drop.index',
    ],
];
