<?php

use App\Modules\PickupDropOptionMaster\Exporters\PickupDropOptionExporter;
use App\Modules\PickupDropOptionMaster\Importers\PickupDropOptionImporter;

return [
    'label' => 'Pickup/Drop Options',
    'description' => 'How the vehicle reaches and leaves the workshop — self drop, workshop pickup/drop, towing or doorstep inspection.',
    'group' => 'Workshop',
    'icon' => 'truck',
    'permissions' => [
        'pickup_drop_option_master.view',
        'pickup_drop_option_master.create',
        'pickup_drop_option_master.update',
        'pickup_drop_option_master.delete',
        'pickup_drop_option_master.export',
        'pickup_drop_option_master.import',
    ],
    'exportable' => PickupDropOptionExporter::class,
    'importable' => PickupDropOptionImporter::class,
];
