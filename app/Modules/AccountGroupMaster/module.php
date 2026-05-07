<?php

use App\Modules\AccountGroupMaster\Exporters\AccountGroupExporter;
use App\Modules\AccountGroupMaster\Importers\AccountGroupImporter;

return [
    'label' => 'Account Groups',
    'description' => 'Top-level chart-of-accounts grouping — Income, Expense, Asset, Liability, Equity.',
    'group' => 'Finance',
    'icon' => 'book-open',
    'permissions' => [
        'account_group_master.view',
        'account_group_master.create',
        'account_group_master.update',
        'account_group_master.delete',
        'account_group_master.export',
        'account_group_master.import',
    ],
    'exportable' => AccountGroupExporter::class,
    'importable' => AccountGroupImporter::class,
];
