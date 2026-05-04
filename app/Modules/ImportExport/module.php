<?php

return [
    'label' => 'Import / Export',
    'description' => 'Shared engine — every module registers Exportable / Importable contracts here.',
    'group' => 'System',
    'icon' => 'arrows-right-left',
    'permissions' => [
        'import_export.view',
        'import_export.run',
    ],
];
