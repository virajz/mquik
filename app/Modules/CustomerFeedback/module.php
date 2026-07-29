<?php

use App\Modules\CustomerFeedback\Models\CustomerFeedback;

return [
    'label' => 'Customer Feedback',
    'description' => 'Post-service ratings (1–5) and feedback, with scheduled post-service follow-up and CSI tracking.',
    'group' => 'CRM',
    'icon' => 'star',
    'permissions' => [
        'customer_feedback.view',
        'customer_feedback.create',
        'customer_feedback.update',
        'customer_feedback.delete',
    ],
    'searchable' => [
        'model' => CustomerFeedback::class,
        'route' => 'customer-feedback.index',
    ],
];
