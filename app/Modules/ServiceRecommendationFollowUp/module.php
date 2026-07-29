<?php

use App\Modules\ServiceRecommendationFollowUp\Models\ServiceRecommendationFollowUp;

return [
    'label' => 'Service Recommendation Follow-Ups',
    'description' => 'Follow up on technician-recommended future services — type, reason, category, conversion and retention.',
    'group' => 'CRM',
    'icon' => 'wrench-screwdriver',
    'permissions' => [
        'service_recommendation_follow_up.view',
        'service_recommendation_follow_up.create',
        'service_recommendation_follow_up.update',
        'service_recommendation_follow_up.delete',
    ],
    'searchable' => [
        'model' => ServiceRecommendationFollowUp::class,
        'route' => 'service-recommendation-follow-up.index',
    ],
];
