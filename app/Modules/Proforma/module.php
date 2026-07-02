<?php

use App\Modules\Proforma\Models\Proforma;

return [
    'label' => 'Proformas',
    'description' => 'Pre-invoice proformas — warranty, approval workflow, insurance deductions and cost-vs-sell profitability.',
    'group' => 'Sales',
    'icon' => 'document-currency-rupee',
    'permissions' => [
        'proforma.view',
        'proforma.create',
        'proforma.update',
        'proforma.delete',
    ],
    'searchable' => [
        'model' => Proforma::class,
        'route' => 'proforma.index',
    ],
];
