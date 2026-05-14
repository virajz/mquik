<?php

return [
    'label' => 'Job History',
    'description' => 'Read-model of every lifecycle event on a Job Card — created, status moves, complaints, inspections, cancellation.',
    'group' => 'Workshop',
    'icon' => 'clock',
    'permissions' => [
        'job_history.view',
    ],
    // No global search; history is queried in context of a specific Job Card.
];
