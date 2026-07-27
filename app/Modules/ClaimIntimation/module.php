<?php

use App\Modules\ClaimIntimation\Models\ClaimIntimation;

return [
    'label' => 'Claim Intimation',
    'description' => 'Register accidental insurance claims — policy, claim type, damage, intimation mode and survey TAT; notify the surveyor.',
    'group' => 'Insurance',
    'icon' => 'shield-exclamation',
    'permissions' => [
        'claim_intimation.view',
        'claim_intimation.create',
        'claim_intimation.update',
        'claim_intimation.delete',
    ],
    'searchable' => [
        'model' => ClaimIntimation::class,
        'route' => 'claim-intimation.index',
    ],
];
