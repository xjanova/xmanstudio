<?php

namespace App\Services\AiChat;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * A counter that moves whenever the catalogue the assistant quotes changes.
 *
 * The assistant's snapshot of products, services and packages is cached, and
 * it used to live for ten minutes whatever happened: an admin who changed a
 * price watched the assistant quote the old one for the rest of that window.
 * The snapshot's cache key now carries this number, and AppServiceProvider
 * bumps it from the models' saved/deleted events, so the next question after
 * a save is answered from the new data.
 */
class KnowledgeVersion
{
    private const KEY = 'ai_chat:knowledge_version';

    public static function current(): int
    {
        return (int) Cache::get(self::KEY, 1);
    }

    public static function bump(): void
    {
        try {
            if (Cache::get(self::KEY) === null) {
                Cache::forever(self::KEY, 2);

                return;
            }

            Cache::increment(self::KEY);
        } catch (Throwable) {
            // A cache hiccup must never fail the admin's save. The snapshot
            // expires on its own within the hour anyway.
        }
    }
}
