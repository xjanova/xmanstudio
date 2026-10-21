<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Moves the support-ticket and project attachments already on the public disk to the private one,
 * in the deploy that stops writing them there (`attachments:privatize`, which logs what it moved,
 * with the old /storage/… addresses to purge from the CDN cache).
 *
 * Never fails the deploy: a file that cannot be moved stays where it is (its customer and admins
 * still open it through the attachment route), the problem goes to the log, and the command can be
 * run again by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        // `migrate --pretend` must not move real files
        if (DB::pretending()) {
            return;
        }

        try {
            Artisan::call('attachments:privatize');
        } catch (Throwable $e) {
            Log::error('Ticket and project attachments could not be moved off the public disk', ['error' => $e->getMessage()]);
        }
    }

    public function down(): void
    {
        // Not undone: moving the attachments back would publish them again
    }
};
