<?php

use App\Modules\RegularSalesInvoice\Models\RegularSalesInvoice;

return [
    'label' => 'Regular Sales Invoices',
    'description' => 'Final regular / insurance sales invoices — spares & labour, tax, gross margin, warranty and payment status.',
    'group' => 'Sales',
    'icon' => 'receipt-percent',
    'permissions' => [
        'regular_sales_invoice.view',
        'regular_sales_invoice.create',
        'regular_sales_invoice.update',
        'regular_sales_invoice.delete',
    ],
    'searchable' => [
        'model' => RegularSalesInvoice::class,
        'route' => 'regular-sales-invoice.index',
    ],
];
