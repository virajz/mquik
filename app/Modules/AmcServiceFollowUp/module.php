<?php

use App\Modules\AmcServiceFollowUp\Models\AmcServiceFollowUp;

return [
    'label' => 'AMC Service Due / Renewal Follow-Ups',
    'description' => 'Reminders and tracking for AMC due services and renewals — attempts, response, conversion and retention.',
    'group' => 'CRM',
    'icon' => 'calendar-days',
    'permissions' => [
        'amc_service_follow_up.view',
        'amc_service_follow_up.create',
        'amc_service_follow_up.update',
        'amc_service_follow_up.delete',
    ],
    'searchable' => [
        'model' => AmcServiceFollowUp::class,
        'route' => 'amc-service-follow-up.index',
    ],
];
