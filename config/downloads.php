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

    /*
    |--------------------------------------------------------------------------
    | Each app's own download route, by product slug
    |--------------------------------------------------------------------------
    |
    | Where the Download button on a licensed product's card in the customer's
    | Download Center (my-account/downloads) goes — see Product::downloadUrl().
    | Every one of these serves the file from xman4289.com itself. A product
    | not listed gets the licence-checked /download/{slug} page when it has an
    | active version, and no button when it has nothing to download. A route
    | this site does not register is skipped the same way, never a 500 page.
    |
    | Tping, SMS Checker and LocalVPN go to the APK itself: their /download
    | pages are product pages, and SMS Checker's answers 404 while it is off
    | sale — the APK stays open for the licences already sold.
    |
    | Not the same list as VersionController::PUBLIC_DOWNLOAD_ROUTES, which
    | holds only the routes an app's own updater can fetch an exact {version}
    | from without a session.
    |
    */
    'app_routes' => [
        'autotradex' => 'autotradex.download',
        'brainx' => 'brainx.download',
        'chanthra-studio' => 'chanthra-studio.download',
        'cluadex-ai-coding-assistant' => 'cluadex.download',
        'gpuxmine' => 'gpuxmine.download',
        'winx-tools' => 'winx-tools.download',
        'aipray' => 'aipray.download',
        'localvpn' => 'localvpn.download.apk',
        'smschecker' => 'smschecker.download.apk',
        'tping' => 'tping.download.apk',
    ],

    /*
    |--------------------------------------------------------------------------
    | What each app runs on, by product slug
    |--------------------------------------------------------------------------
    |
    | The Platform row on the same cards — see Product::downloadPlatform(). A
    | product not listed shows no Platform row: the card says nothing rather
    | than guess.
    |
    | Listed here, not read from the release's file name: CluadeX and WinXTools
    | ship a .zip, which says nothing about the OS, and an app with no version
    | synced yet has no file name at all (AutoTradeX, 2026-09-24).
    |
    */
    'app_platforms' => [
        'autotradex' => 'Windows',
        'brainx' => 'Windows',
        'chanthra-studio' => 'Windows',
        'cluadex-ai-coding-assistant' => 'Windows',
        'gpuxmine' => 'Windows',
        'winx-tools' => 'Windows',
        'aipray' => 'Android',
        'localvpn' => 'Android',
        'smschecker' => 'Android',
        'tping' => 'Android',
    ],
];
