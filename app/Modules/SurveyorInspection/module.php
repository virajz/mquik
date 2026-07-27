<?php

use App\Modules\SurveyorInspection\Models\SurveyorInspection;

return [
    'label' => 'Surveyor Inspection',
    'description' => 'Record the insurance surveyor\'s findings — survey type, per-line repair/replace decisions, approval and not-covered reasons.',
    'group' => 'Insurance',
    'icon' => 'magnifying-glass-circle',
    'permissions' => [
        'surveyor_inspection.view',
        'surveyor_inspection.create',
        'surveyor_inspection.update',
        'surveyor_inspection.delete',
    ],
    'searchable' => [
        'model' => SurveyorInspection::class,
        'route' => 'surveyor-inspection.index',
    ],
];
