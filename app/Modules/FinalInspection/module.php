<?php

use App\Modules\FinalInspection\Models\FinalInspection;

return [
    'label' => 'Final Inspections',
    'description' => 'Pre-delivery quality check — checklist, results, before/after/damage photos, rework & completion status.',
    'group' => 'Inspection',
    'icon' => 'check-badge',
    'permissions' => [
        'final_inspection.view',
        'final_inspection.create',
        'final_inspection.update',
        'final_inspection.delete',
    ],
    'searchable' => [
        'model' => FinalInspection::class,
        'route' => 'final-inspection.index',
    ],
];
