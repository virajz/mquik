<?php

use App\Modules\ServiceDueFollowUp\Models\ServiceDueFollowUp;

return [
    'label' => 'Service Due Follow-Ups',
    'description' => 'Track upcoming scheduled-service due follow-ups — attempts, customer response, escalation, retention and conversion.',
    'group' => 'CRM',
    'icon' => 'calendar-days',
    'permissions' => [
        'service_due_follow_up.view',
        'service_due_follow_up.create',
        'service_due_follow_up.update',
        'service_due_follow_up.delete',
    ],
    'searchable' => [
        'model' => ServiceDueFollowUp::class,
        'route' => 'service-due-follow-up.index',
    ],
];
