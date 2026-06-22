<?php

use App\Modules\DamageCauseMaster\Exporters\DamageCauseExporter;
use App\Modules\DamageCauseMaster\Importers\DamageCauseImporter;

return [
    'label' => 'Damage Causes',
    'description' => 'Damage cause categories for estimates and insurance claims.',
    'group' => 'Sales',
    'icon' => 'bolt',
    'permissions' => [
        'damage_cause_master.view',
        'damage_cause_master.create',
        'damage_cause_master.update',
        'damage_cause_master.delete',
        'damage_cause_master.export',
        'damage_cause_master.import',
    ],
    'exportable' => DamageCauseExporter::class,
    'importable' => DamageCauseImporter::class,
];
