<?php

use App\Modules\EstimateTemplateMaster\Models\EstimateTemplateMaster;

return [
    'label' => 'Estimate Templates',
    'description' => 'Group-wise default spare/labour sets used to pre-fill sales estimates.',
    'group' => 'Sales',
    'icon' => 'document-duplicate',
    'permissions' => [
        'estimate_template_master.view',
        'estimate_template_master.create',
        'estimate_template_master.update',
        'estimate_template_master.delete',
    ],
    'searchable' => [
        'model' => EstimateTemplateMaster::class,
        'route' => 'estimate-template-master.index',
    ],
];
