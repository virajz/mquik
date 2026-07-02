<?php

use App\Modules\IncentivePolicyMaster\Exporters\IncentivePolicyExporter;
use App\Modules\IncentivePolicyMaster\Importers\IncentivePolicyImporter;

return [
    'label' => 'Incentive Policies',
    'description' => 'Incentive policies - labour sales, parts sales, satisfaction, efficiency.',
    'group' => 'HR',
    'icon' => 'gift',
    'permissions' => [
        'incentive_policy_master.view',
        'incentive_policy_master.create',
        'incentive_policy_master.update',
        'incentive_policy_master.delete',
        'incentive_policy_master.export',
        'incentive_policy_master.import',
    ],
    'exportable' => IncentivePolicyExporter::class,
    'importable' => IncentivePolicyImporter::class,
];
