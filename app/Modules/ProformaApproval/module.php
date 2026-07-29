<?php

use App\Modules\ProformaApproval\Models\ProformaApproval;

return [
    'label' => 'Proforma Approval',
    'description' => 'Staged digital approval of a proforma (billing → store/advisor → admin) before invoice conversion, with per-role checkpoints.',
    'group' => 'Sales',
    'icon' => 'clipboard-document-check',
    'permissions' => [
        'proforma_approval.view',
        'proforma_approval.create',
        'proforma_approval.update',
        'proforma_approval.delete',
    ],
    'searchable' => [
        'model' => ProformaApproval::class,
        'route' => 'proforma-approval.index',
    ],
];
