<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feature switches
    |--------------------------------------------------------------------------
    |
    | Parts of the app that are still being shaped and may want turning off in
    | a demo or on a particular environment.
    |
    */

    'features' => [

        /*
         * The standing alert strip above the page content ("car delivery
         * delayed", "vendor parts not received"). Off hides the strip entirely;
         * the notifications themselves are still recorded and still reachable
         * from the bell and the Notifications screen.
         */
        'alert_banner' => env('MQUIK_ALERT_BANNER', true),

        /*
         * The admin-only "View as role" control above the page content.
         */
        'role_preview' => env('MQUIK_ROLE_PREVIEW', true),

    ],

];
