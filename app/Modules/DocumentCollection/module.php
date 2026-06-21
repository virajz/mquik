<?php

use App\Modules\DocumentCollection\Models\DocumentCollection;

return [
    'label' => 'Document Collection',
    'description' => 'Collect & verify customer / insurance documents against a checklist — request, receive, verify, attach scans, and track status.',
    'group' => 'Insurance',
    'icon' => 'document-arrow-up',
    'permissions' => [
        'document_collection.view',
        'document_collection.create',
        'document_collection.update',
        'document_collection.delete',
    ],
    'searchable' => [
        'model' => DocumentCollection::class,
        'route' => 'document-collection.index',
    ],
];
