<?php

use App\Modules\SalesReturn\Models\SalesReturn;

return [
    'label' => 'Sales Returns',
    'description' => 'Returns across regular / insurance / counter sales — reason, refund status, and stock restore.',
    'group' => 'Sales',
    'icon' => 'arrow-uturn-left',
    'permissions' => [
        'sales_return.view',
        'sales_return.create',
        'sales_return.update',
        'sales_return.delete',
    ],
    'searchable' => [
        'model' => SalesReturn::class,
        'route' => 'sales-return.index',
    ],
];
