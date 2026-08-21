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

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */

    'security' => [

        /*
         * Minutes of inactivity before a user is signed out. The default lives
         * in config/session.php; a value saved in Workshop Settings overrides it
         * at boot, so an admin can change it without a deploy.
         */
        'session_lifetime' => env('SESSION_LIFETIME', 30),

    ],

    /*
    |--------------------------------------------------------------------------
    | Service history
    |--------------------------------------------------------------------------
    |
    | The "what has this vehicle had done" panel on a job card. Per-service due
    | intervals live in the Service Intervals master; these are the display
    | limits and the fallback used when a service has no interval configured.
    |
    */

    'service_history' => [

        /*
         * Default order for the "Last done" list. Options are declared on
         * App\Modules\JobCard\Concerns\ShowsServiceHistory.
         */
        'sort_mode' => env('MQUIK_SERVICE_SORT', 'due_first'),

        // How many services to list under "Last done".
        'services_shown' => env('MQUIK_SERVICE_HISTORY_SHOWN', 8),

        // Past visits scanned when building that list.
        'visits_scanned' => env('MQUIK_SERVICE_HISTORY_SCAN', 100),

        // Past visits listed underneath.
        'visits_listed' => env('MQUIK_SERVICE_HISTORY_VISITS', 50),

        /*
         * Fallback age, in months, for a service with no configured interval.
         * Set to null to flag nothing without an explicit interval.
         */
        'default_overdue_months' => env('MQUIK_SERVICE_OVERDUE_MONTHS', 12),

    ],

];
