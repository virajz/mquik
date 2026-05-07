<?php

use App\Modules\BankMaster\Exporters\BankExporter;
use App\Modules\BankMaster\Importers\BankImporter;

return [
    'label' => 'Banks',
    'description' => 'Banks used by the workshop, vendors, and employees for payments and deposits.',
    'group' => 'Finance',
    'icon' => 'building-library',
    'permissions' => [
        'bank_master.view',
        'bank_master.create',
        'bank_master.update',
        'bank_master.delete',
        'bank_master.export',
        'bank_master.import',
    ],
    'exportable' => BankExporter::class,
    'importable' => BankImporter::class,
];
