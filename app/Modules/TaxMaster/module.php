<?php

use App\Modules\TaxMaster\Exporters\TaxExporter;
use App\Modules\TaxMaster\Importers\TaxImporter;

return [
    'label' => 'Taxes',
    'description' => 'GST/HSN tax codes used by spares, labour, and invoices.',
    'group' => 'Finance',
    'icon' => 'calculator',
    'permissions' => [
        'tax_master.view',
        'tax_master.create',
        'tax_master.update',
        'tax_master.delete',
        'tax_master.export',
        'tax_master.import',
    ],
    'exportable' => TaxExporter::class,
    'importable' => TaxImporter::class,
];
