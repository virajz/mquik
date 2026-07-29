<?php

use App\Modules\OutsideLabourCreditNote\Models\OutsideLabourCreditNote;

return [
    'label' => 'Outside Labour Credit / Debit Note',
    'description' => 'Raise a CN / DN against an outside-labour bill or warranty return to settle an adjustment commercially.',
    'group' => 'Workshop',
    'icon' => 'receipt-refund',
    'permissions' => [
        'outside_labour_credit_note.view',
        'outside_labour_credit_note.create',
        'outside_labour_credit_note.update',
        'outside_labour_credit_note.delete',
    ],
    'searchable' => [
        'model' => OutsideLabourCreditNote::class,
        'route' => 'outside-labour-credit-note.index',
    ],
];
