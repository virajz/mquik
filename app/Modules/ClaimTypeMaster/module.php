<?php

use App\Modules\ClaimTypeMaster\Exporters\ClaimTypeExporter;
use App\Modules\ClaimTypeMaster\Importers\ClaimTypeImporter;

return [
    'label' => 'Claim Types',
    'description' => 'Insurance claim types — cashless, reimbursement, accident, theft, total loss.',
    'group' => 'Insurance',
    'icon' => 'shield-check',
    'permissions' => [
        'claim_type_master.view',
        'claim_type_master.create',
        'claim_type_master.update',
        'claim_type_master.delete',
        'claim_type_master.export',
        'claim_type_master.import',
    ],
    'exportable' => ClaimTypeExporter::class,
    'importable' => ClaimTypeImporter::class,
];
