<?php

use App\Modules\InternalPartsInquiry\Models\InternalPartsInquiry;

return [
    'label' => 'Internal Parts Inquiry',
    'description' => 'Advisor → store-incharge stock check — what spares are needed for which job card.',
    'group' => 'Inventory',
    'icon' => 'inbox-arrow-down',
    'permissions' => [
        'internal_parts_inquiry.view',
        'internal_parts_inquiry.create',
        'internal_parts_inquiry.update',
        'internal_parts_inquiry.delete',
    ],
    'searchable' => [
        'model' => InternalPartsInquiry::class,
        'route' => 'internal-parts-inquiry.index',
    ],
];
