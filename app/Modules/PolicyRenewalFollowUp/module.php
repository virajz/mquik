<?php

use App\Modules\PolicyRenewalFollowUp\Models\PolicyRenewalFollowUp;

return [
    'label' => 'Policy Renewal Follow-Ups',
    'description' => 'Track follow-ups for upcoming insurance policy renewals — reminders, attempts, response, renewal or loss.',
    'group' => 'Insurance',
    'icon' => 'shield-exclamation',
    'permissions' => [
        'policy_renewal_follow_up.view',
        'policy_renewal_follow_up.create',
        'policy_renewal_follow_up.update',
        'policy_renewal_follow_up.delete',
    ],
    'searchable' => [
        'model' => PolicyRenewalFollowUp::class,
        'route' => 'policy-renewal-follow-up.index',
    ],
];
