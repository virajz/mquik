<?php

use App\Modules\VehicleSegmentMaster\Exporters\VehicleSegmentExporter;
use App\Modules\VehicleSegmentMaster\Importers\VehicleSegmentImporter;

return [
    'label' => 'Vehicle Segments',
    'description' => 'Body-type segments used by Vehicle Models, Job Cards, and reporting.',
    'group' => 'Vehicles',
    'icon' => 'squares-plus',
    'permissions' => [
        'vehicle_segment_master.view',
        'vehicle_segment_master.create',
        'vehicle_segment_master.update',
        'vehicle_segment_master.delete',
        'vehicle_segment_master.export',
        'vehicle_segment_master.import',
    ],
    'exportable' => VehicleSegmentExporter::class,
    'importable' => VehicleSegmentImporter::class,
];
