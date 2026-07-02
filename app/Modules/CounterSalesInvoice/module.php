<?php

use App\Modules\CounterSalesInvoice\Models\CounterSalesInvoice;

return [
    'label' => 'Counter Sales Invoices',
    'description' => 'Over-the-counter parts sales — delivery type, courier, tax, gross margin; finalizing deducts stock.',
    'group' => 'Sales',
    'icon' => 'shopping-bag',
    'permissions' => [
        'counter_sales_invoice.view',
        'counter_sales_invoice.create',
        'counter_sales_invoice.update',
        'counter_sales_invoice.delete',
    ],
    'searchable' => [
        'model' => CounterSalesInvoice::class,
        'route' => 'counter-sales-invoice.index',
    ],
];
