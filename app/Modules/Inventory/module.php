<?php

return [
    'label' => 'Inventory',
    'description' => 'Live stock levels, FIFO ledger, and MIN/MAX alerts for all spares.',
    'group' => 'Inventory',
    'icon' => 'archive-box',
    'permissions' => [
        'inventory.view',
    ],
    'searchable' => null,  // Not directly searchable — use the Inventory index
];
