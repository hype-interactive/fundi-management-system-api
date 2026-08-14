<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scan Wall Event Key
    |--------------------------------------------------------------------------
    |
    | Gates fundi_attendances recording in FundiController::show(). Empty
    | means no attendance write happens at all. The verify endpoint runs
    | year-round for ordinary field verification; set this before a meeting
    | starts and clear it after so ordinary scans are never recorded.
    |
    */
    'scan_event_key' => env('SCAN_EVENT_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Scan Wall Access Key
    |--------------------------------------------------------------------------
    |
    | Static shared token required as the `key` query parameter on the public
    | GET /api/v1/wall/arrivals endpoint. Weak auth by design — it only stops
    | casual URL discovery and drive-by scraping. Empty means the endpoint is
    | closed (never matches an empty/missing request key).
    |
    */
    'access_key' => env('WALL_ACCESS_KEY', ''),

];
