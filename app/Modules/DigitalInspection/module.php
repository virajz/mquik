<?php

use App\Modules\DigitalInspection\Models\DigitalInspection;

return [
    'label' => 'Digital Inspections',
    'description' => 'Per-job inspection checklist execution — Rep / Adj / OK / IA / FA outcomes per item.',
    'group' => 'Inspection',
    'icon' => 'magnifying-glass-circle',
    'permissions' => [
        'digital_inspection.view',
        'digital_inspection.create',
        'digital_inspection.update',
        'digital_inspection.delete',
    ],
    'searchable' => [
        'model' => DigitalInspection::class,
        'route' => 'digital-inspection.index',
    ],
];
