<?php

return [
    'label' => 'Recommendation Descriptions',
    'description' => 'The wording a technician picks when recommending work on an inspection checkpoint.',
    'group' => 'Masters',
    'icon' => 'chat-bubble-bottom-center-text',
    'permissions' => [
        'recommendation_description_master.view',
        'recommendation_description_master.create',
        'recommendation_description_master.update',
        'recommendation_description_master.delete',
    ],
];
