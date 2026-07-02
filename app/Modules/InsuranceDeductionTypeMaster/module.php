<?php

use App\Modules\InsuranceDeductionTypeMaster\Exporters\InsuranceDeductionTypeExporter;
use App\Modules\InsuranceDeductionTypeMaster\Importers\InsuranceDeductionTypeImporter;

return [
    'label' => 'Insurance Deduction Types',
    'description' => 'Charges an insurer deducts from a claim payout.',
    'group' => 'Insurance',
    'icon' => 'receipt-percent',
    'permissions' => [
        'insurance_deduction_type_master.view',
        'insurance_deduction_type_master.create',
        'insurance_deduction_type_master.update',
        'insurance_deduction_type_master.delete',
        'insurance_deduction_type_master.export',
        'insurance_deduction_type_master.import',
    ],
    'exportable' => InsuranceDeductionTypeExporter::class,
    'importable' => InsuranceDeductionTypeImporter::class,
];
