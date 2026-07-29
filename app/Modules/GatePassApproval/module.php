<?php

use App\Modules\GatePassApproval\Models\GatePassApproval;

return [
    'label' => 'Gate Pass Approval',
    'description' => 'Approve vehicle delivery against outstanding (credit / post-dated cheque / no DO) with amount-matrix routing.',
    'group' => 'Sales',
    'icon' => 'shield-check',
    'permissions' => [
        'gate_pass_approval.view',
        'gate_pass_approval.create',
        'gate_pass_approval.update',
        'gate_pass_approval.delete',
    ],
    'searchable' => [
        'model' => GatePassApproval::class,
        'route' => 'gate-pass-approval.index',
    ],
];
