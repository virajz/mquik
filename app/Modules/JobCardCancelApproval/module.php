<?php

use App\Modules\JobCardCancelApproval\Models\JobCardCancelApproval;

return [
    'label' => 'Job Card Cancel Approval',
    'description' => 'Admin approval workflow for job card cancellation — type/reason, 4-level hierarchy, impacts and refund status.',
    'group' => 'Workshop',
    'icon' => 'x-circle',
    'permissions' => [
        'job_card_cancel_approval.view',
        'job_card_cancel_approval.create',
        'job_card_cancel_approval.update',
        'job_card_cancel_approval.delete',
    ],
    'searchable' => [
        'model' => JobCardCancelApproval::class,
        'route' => 'job-card-cancel-approval.index',
    ],
];
