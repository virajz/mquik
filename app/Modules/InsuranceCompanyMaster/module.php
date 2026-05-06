<?php

use App\Modules\InsuranceCompanyMaster\Exporters\InsuranceCompanyExporter;
use App\Modules\InsuranceCompanyMaster\Importers\InsuranceCompanyImporter;

return [
    'label' => 'Insurance Companies',
    'description' => 'Insurers this workshop deals with for claims and policy renewals.',
    'group' => 'Insurance',
    'icon' => 'shield-check',
    'permissions' => [
        'insurance_company_master.view',
        'insurance_company_master.create',
        'insurance_company_master.update',
        'insurance_company_master.delete',
        'insurance_company_master.export',
        'insurance_company_master.import',
    ],
    'exportable' => InsuranceCompanyExporter::class,
    'importable' => InsuranceCompanyImporter::class,
];
