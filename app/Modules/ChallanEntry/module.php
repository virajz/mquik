<?php

use App\Modules\ChallanEntry\Models\Challan;

return [
    'label' => 'Challan Entries',
    'description' => 'Inward part entry via delivery challan — material condition, discounts, charges, invoice & inventory status.',
    'group' => 'Purchase',
    'icon' => 'document-text',
    'permissions' => [
        'challan_entry.view',
        'challan_entry.create',
        'challan_entry.update',
        'challan_entry.delete',
    ],
    'searchable' => [
        'model' => Challan::class,
        'route' => 'challan-entry.index',
    ],
];
