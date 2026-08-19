<?php

use App\Modules\ServiceIntervalMaster\Exporters\ServiceIntervalExporter;
use App\Modules\ServiceIntervalMaster\Importers\ServiceIntervalImporter;
use App\Modules\ServiceIntervalMaster\Models\ServiceIntervalMaster;

return [
    'label' => 'Service Intervals',
    'description' => 'How often each service falls due — by months, by kilometres, or both. Drives the "what is due" list on a vehicle\'s service history.',
    'group' => 'Workshop',
    'icon' => 'clock',
    'permissions' => [
        'service_interval_master.view',
        'service_interval_master.create',
        'service_interval_master.update',
        'service_interval_master.delete',
    ],
    'exportable' => ServiceIntervalExporter::class,
    'importable' => ServiceIntervalImporter::class,
    'searchable' => [
        'model' => ServiceIntervalMaster::class,
        'route' => 'service-interval-master.index',
    ],
];
