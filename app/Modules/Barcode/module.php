<?php

return [
    'label' => 'Barcode',
    'description' => 'Barcode label generation, print, and scan-to-spare resolution.',
    'group' => 'Inventory',
    'icon' => 'qr-code',
    'permissions' => [
        'barcode.view',
        'barcode.create',
        'barcode.delete',
    ],
    'searchable' => null,
];
