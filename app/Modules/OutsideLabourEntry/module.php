<?php

use App\Modules\OutsideLabourEntry\Models\OutsideLabourEntry;

return [
    'label' => 'Outside Labour Entries',
    'description' => 'Record outside vendor work (parts + labour) against a physical invoice, with attachments.',
    'group' => 'Purchase',
    'icon' => 'wrench',
    'permissions' => [
        'outside_labour_entry.view',
        'outside_labour_entry.create',
        'outside_labour_entry.update',
        'outside_labour_entry.delete',
    ],
    'searchable' => [
        'model' => OutsideLabourEntry::class,
        'route' => 'outside-labour-entry.index',
    ],
];
