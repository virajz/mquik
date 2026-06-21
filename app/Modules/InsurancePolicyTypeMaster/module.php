<?php

use App\Modules\InsurancePolicyTypeMaster\Exporters\InsurancePolicyTypeExporter;
use App\Modules\InsurancePolicyTypeMaster\Importers\InsurancePolicyTypeImporter;

return [
    'label' => 'Insurance Policy Types',
    'description' => 'Motor insurance policy types — comprehensive, third party, zero dep, corporate.',
    'group' => 'Insurance',
    'icon' => 'document-text',
    'permissions' => [
        'insurance_policy_type_master.view',
        'insurance_policy_type_master.create',
        'insurance_policy_type_master.update',
        'insurance_policy_type_master.delete',
        'insurance_policy_type_master.export',
        'insurance_policy_type_master.import',
    ],
    'exportable' => InsurancePolicyTypeExporter::class,
    'importable' => InsurancePolicyTypeImporter::class,
];
