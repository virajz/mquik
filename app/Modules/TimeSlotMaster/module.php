<?php

use App\Modules\TimeSlotMaster\Exporters\TimeSlotExporter;
use App\Modules\TimeSlotMaster\Importers\TimeSlotImporter;

return [
    'label' => 'Time Slots',
    'description' => 'Bookable appointment windows — start/end time, vehicle capacity per slot and optional buffer.',
    'group' => 'Workshop',
    'icon' => 'clock',
    'permissions' => [
        'time_slot_master.view',
        'time_slot_master.create',
        'time_slot_master.update',
        'time_slot_master.delete',
        'time_slot_master.export',
        'time_slot_master.import',
    ],
    'exportable' => TimeSlotExporter::class,
    'importable' => TimeSlotImporter::class,
];
