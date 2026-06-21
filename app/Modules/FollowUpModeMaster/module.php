<?php

use App\Modules\FollowUpModeMaster\Exporters\FollowUpModeExporter;
use App\Modules\FollowUpModeMaster\Importers\FollowUpModeImporter;

return [
    'label' => 'Follow-up Modes',
    'description' => 'How the workshop follows up for documents — call, SMS, WhatsApp, email, visit.',
    'group' => 'Insurance',
    'icon' => 'phone',
    'permissions' => [
        'follow_up_mode_master.view',
        'follow_up_mode_master.create',
        'follow_up_mode_master.update',
        'follow_up_mode_master.delete',
        'follow_up_mode_master.export',
        'follow_up_mode_master.import',
    ],
    'exportable' => FollowUpModeExporter::class,
    'importable' => FollowUpModeImporter::class,
];
