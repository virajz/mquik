<?php

use App\Modules\StandardObservationMaster\Exporters\StandardObservationExporter;
use App\Modules\StandardObservationMaster\Importers\StandardObservationImporter;

return [
    'label' => 'Standard Observations',
    'description' => 'Reusable inspection observations for faster entry (e.g. Brake pads worn out, Oil leakage).',
    'group' => 'Inspection',
    'icon' => 'chat-bubble-bottom-center-text',
    'permissions' => [
        'standard_observation_master.view',
        'standard_observation_master.create',
        'standard_observation_master.update',
        'standard_observation_master.delete',
        'standard_observation_master.export',
        'standard_observation_master.import',
    ],
    'exportable' => StandardObservationExporter::class,
    'importable' => StandardObservationImporter::class,
];
