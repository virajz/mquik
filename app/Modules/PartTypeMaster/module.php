<?php

use App\Modules\PartTypeMaster\Exporters\PartTypeExporter;
use App\Modules\PartTypeMaster\Importers\PartTypeImporter;

return [
    'label' => 'Part Types',
    'description' => 'Part sourcing types — Genuine, After Market, OEM, Refurbished.',
    'group' => 'Inventory',
    'icon' => 'tag',
    'permissions' => [
        'part_type_master.view',
        'part_type_master.create',
        'part_type_master.update',
        'part_type_master.delete',
        'part_type_master.export',
        'part_type_master.import',
    ],
    'exportable' => PartTypeExporter::class,
    'importable' => PartTypeImporter::class,
];
