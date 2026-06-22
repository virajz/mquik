<?php

use App\Modules\ChargeTypeMaster\Exporters\ChargeTypeExporter;
use App\Modules\ChargeTypeMaster\Importers\ChargeTypeImporter;

return [
    'label' => 'Charge Types',
    'description' => 'Additional charge heads (Freight, P and F, Insurance...).',
    'group' => 'Purchase',
    'icon' => 'banknotes',
    'permissions' => [
        'charge_type_master.view',
        'charge_type_master.create',
        'charge_type_master.update',
        'charge_type_master.delete',
        'charge_type_master.export',
        'charge_type_master.import',
    ],
    'exportable' => ChargeTypeExporter::class,
    'importable' => ChargeTypeImporter::class,
];
