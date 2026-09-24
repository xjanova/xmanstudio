<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Files streamed to customers from xman4289.com
    |--------------------------------------------------------------------------
    |
    | Release files live on GitHub, but customers must only ever see
    | xman4289.com (owner rule, 2026-09-24), so ReleaseDownloadStreamer pulls
    | them through the server. Apache hands each download to one PHP-FPM
    | worker for as long as the customer takes to receive it — a CluadeX
    | installer is ~480 MB — so only this many run at once. The next customer
    | gets 503 + Retry-After instead of the licence API and the shop running
    | out of workers. Keep it well below pm.max_children (50 in production).
    | 0 turns the limit off.
    |
    */
    'max_concurrent_streams' => (int) env('DOWNLOAD_MAX_STREAMS', 10),

    /*
    | A worker killed mid-download (a deploy restarts PHP-FPM) never frees its
    | place, so every place expires on its own after this many seconds. A
    | download that runs longer keeps going; it just stops being counted.
    */
    'stream_slot_seconds' => (int) env('DOWNLOAD_STREAM_SLOT_SECONDS', 1200),
];
