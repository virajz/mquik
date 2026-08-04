<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Block negative stock
    |--------------------------------------------------------------------------
    |
    | When true, issuing more than the ledger holds is rejected. Set to false
    | for workshops that fit parts first and reconcile the paperwork later —
    | the shortfall is then recorded as an unlayered OUT entry, valued at the
    | spare's most recent purchase rate.
    |
    */

    'block_negative_stock' => env('INVENTORY_BLOCK_NEGATIVE_STOCK', true),

    /*
    |--------------------------------------------------------------------------
    | Expiry warning window
    |--------------------------------------------------------------------------
    |
    | Days ahead that a batch counts as "expiring soon" in the stock report.
    |
    */

    'expiry_warning_days' => env('INVENTORY_EXPIRY_WARNING_DAYS', 90),

];
