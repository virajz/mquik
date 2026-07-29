<?php

use App\Modules\AdvanceReceiptRequest\Models\AdvanceReceiptRequest;

return [
    'label' => 'Advance Receipt Request',
    'description' => 'Request an advance payment from the customer — purpose, amount basis, reminders and follow-up.',
    'group' => 'Finance',
    'icon' => 'banknotes',
    'permissions' => [
        'advance_receipt_request.view',
        'advance_receipt_request.create',
        'advance_receipt_request.update',
        'advance_receipt_request.delete',
    ],
    'searchable' => [
        'model' => AdvanceReceiptRequest::class,
        'route' => 'advance-receipt-request.index',
    ],
];
