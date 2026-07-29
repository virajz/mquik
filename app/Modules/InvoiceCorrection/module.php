<?php

use App\Modules\InvoiceCorrection\Models\InvoiceCorrection;

return [
    'label' => 'Invoice Correction',
    'description' => 'Advisor-initiated, admin-approved correction of a raised invoice — correct in place, credit-note & reissue, or cancel & reissue.',
    'group' => 'Sales',
    'icon' => 'pencil-square',
    'permissions' => [
        'invoice_correction.view',
        'invoice_correction.create',
        'invoice_correction.update',
        'invoice_correction.delete',
    ],
    'searchable' => [
        'model' => InvoiceCorrection::class,
        'route' => 'invoice-correction.index',
    ],
];
