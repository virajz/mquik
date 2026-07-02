<?php

use App\Modules\LoanTypeMaster\Exporters\LoanTypeExporter;
use App\Modules\LoanTypeMaster\Importers\LoanTypeImporter;

return [
    'label' => 'Loan Types',
    'description' => 'Types of employee loan / advance.',
    'group' => 'HR',
    'icon' => 'banknotes',
    'permissions' => [
        'loan_type_master.view',
        'loan_type_master.create',
        'loan_type_master.update',
        'loan_type_master.delete',
        'loan_type_master.export',
        'loan_type_master.import',
    ],
    'exportable' => LoanTypeExporter::class,
    'importable' => LoanTypeImporter::class,
];
