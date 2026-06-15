<?php

use App\Modules\DamageTypeMaster\Exporters\DamageTypeExporter;
use App\Modules\DamageTypeMaster\Importers\DamageTypeImporter;

return [
    'label' => 'Damage Types',
    'description' => 'Nature of vehicle damage — scratch, dent, crack, rust, broken, paint fade. Tags photos and inventory items.',
    'group' => 'Workshop',
    'icon' => 'bolt',
    'permissions' => [
        'damage_type_master.view',
        'damage_type_master.create',
        'damage_type_master.update',
        'damage_type_master.delete',
        'damage_type_master.export',
        'damage_type_master.import',
    ],
    'exportable' => DamageTypeExporter::class,
    'importable' => DamageTypeImporter::class,
];
