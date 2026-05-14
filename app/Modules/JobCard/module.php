<?php

use App\Modules\JobCard\Models\JobCard;

return [
    'label' => 'Job Cards',
    'description' => 'The central transactional record — vehicle in, complaints captured, work performed, status tracked.',
    'group' => 'Workshop',
    'icon' => 'clipboard-document-list',
    'permissions' => [
        'job_card.view',
        'job_card.create',
        'job_card.update',
        'job_card.delete',
        'job_card.cancel',
    ],
    'searchable' => [
        'model' => JobCard::class,
        'route' => 'job-card.index',
    ],
];
