<?php

use App\Modules\DistanceSlabMaster\Exporters\DistanceSlabExporter;
use App\Modules\DistanceSlabMaster\Importers\DistanceSlabImporter;

return [
    'label' => 'Distance Slabs',
    'description' => 'Pickup/drop distance bands and their charges — 0-5 KM at 200, 6-15 KM at 300.',
    'group' => 'Workshop',
    'icon' => 'map-pin',
    'permissions' => [
        'distance_slab_master.view',
        'distance_slab_master.create',
        'distance_slab_master.update',
        'distance_slab_master.delete',
        'distance_slab_master.export',
        'distance_slab_master.import',
    ],
    'exportable' => DistanceSlabExporter::class,
    'importable' => DistanceSlabImporter::class,
];
