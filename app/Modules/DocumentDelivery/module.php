<?php

use App\Modules\DocumentDelivery\Models\DocumentDelivery;

return [
    'label' => 'Document Delivery',
    'description' => 'Track outbound document delivery to customer/insurer — mode, acknowledgement, checklist and status.',
    'group' => 'Insurance',
    'icon' => 'paper-airplane',
    'permissions' => [
        'document_delivery.view',
        'document_delivery.create',
        'document_delivery.update',
        'document_delivery.delete',
    ],
    'searchable' => [
        'model' => DocumentDelivery::class,
        'route' => 'document-delivery.index',
    ],
];
