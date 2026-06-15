<?php

use App\Modules\CustomerApprovalTypeMaster\Exporters\CustomerApprovalTypeExporter;
use App\Modules\CustomerApprovalTypeMaster\Importers\CustomerApprovalTypeImporter;

return [
    'label' => 'Customer Approval Types',
    'description' => 'How the customer approved the work — in person, phone, WhatsApp, email, digital signature.',
    'group' => 'Workshop',
    'icon' => 'check-badge',
    'permissions' => [
        'customer_approval_type_master.view',
        'customer_approval_type_master.create',
        'customer_approval_type_master.update',
        'customer_approval_type_master.delete',
        'customer_approval_type_master.export',
        'customer_approval_type_master.import',
    ],
    'exportable' => CustomerApprovalTypeExporter::class,
    'importable' => CustomerApprovalTypeImporter::class,
];
