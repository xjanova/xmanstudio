<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Encrypts, in place, the secrets that were added to Setting::$encryptedKeys on
 * 2026-09-23. Values saved before then are plaintext; Setting::getValue still
 * reads them (it falls back to the raw value) but logs a warning on every read,
 * and the database keeps holding the secret in the clear until someone re-saves.
 */
return new class extends Migration
{
    private const KEYS = [
        'line_channel_access_token',
        'line_channel_secret',
        'metalx_youtube_access_token',
        'freepik_api_key',
    ];

    public function up(): void
    {
        foreach (self::KEYS as $key) {
            $row = DB::table('settings')->where('key', $key)->first();

            if (! $row || $row->value === null || $row->value === '') {
                continue;
            }

            try {
                Crypt::decryptString($row->value);

                continue; // already encrypted
            } catch (DecryptException) {
                // plaintext — encrypt it below
            }

            DB::table('settings')->where('id', $row->id)->update([
                'value' => Crypt::encryptString($row->value),
            ]);

            Cache::forget("setting.{$key}");
        }
    }

    /**
     * Deliberately empty: writing secrets back into the table in the clear is not
     * a rollback anyone wants. Setting::getValue reads either form.
     */
    public function down(): void
    {
        //
    }
};
