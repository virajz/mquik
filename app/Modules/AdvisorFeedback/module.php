<?php

use App\Modules\AdvisorFeedback\Models\AdvisorFeedback;

return [
    'label' => 'Advisor Feedback',
    'description' => 'The advisor rates the customer after a job — cooperation, approvals, payment, professionalism and repeat preference.',
    'group' => 'CRM',
    'icon' => 'user-circle',
    'permissions' => [
        'advisor_feedback.view',
        'advisor_feedback.create',
        'advisor_feedback.update',
        'advisor_feedback.delete',
    ],
    'searchable' => [
        'model' => AdvisorFeedback::class,
        'route' => 'advisor-feedback.index',
    ],
];
