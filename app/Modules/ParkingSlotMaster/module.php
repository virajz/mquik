<?php

use App\Modules\ParkingSlotMaster\Exporters\ParkingSlotExporter;
use App\Modules\ParkingSlotMaster\Importers\ParkingSlotImporter;

return [
    'label' => 'Parking Slots',
    'description' => 'Where a vehicle waits on site — numbered slots plus outside-the-gate parking.',
    'group' => 'Workshop',
    'icon' => 'squares-2x2',
    'permissions' => [
        'parking_slot_master.view',
        'parking_slot_master.create',
        'parking_slot_master.update',
        'parking_slot_master.delete',
        'parking_slot_master.export',
        'parking_slot_master.import',
    ],
    'exportable' => ParkingSlotExporter::class,
    'importable' => ParkingSlotImporter::class,
];
