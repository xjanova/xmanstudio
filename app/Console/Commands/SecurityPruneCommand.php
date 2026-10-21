<?php

namespace App\Console\Commands;

use App\Models\BlockedIp;
use App\Models\LoginAttempt;
use Illuminate\Console\Command;

/**
 * Keeps the login log from becoming a permanent record of who signs in from
 * where, and clears out blocks that have already run their course.
 *
 * Expired blocks are not enforced (BlockedIp::scopeActive excludes them), so
 * deleting them is tidying, not a change in behaviour — but leaving them would
 * make the block list unreadable within a week of the first bot storm.
 */
class SecurityPruneCommand extends Command
{
    protected $signature = 'security:prune {--days= : Override the retention window}';

    protected $description = 'Delete old login attempts and expired IP blocks';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('security.login_log_days', 90));

        if ($days < 1) {
            $this->error('Retention must be at least one day.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        // Chunked: a year of a busy site is a large DELETE, and one statement
        // holding the table would stall every login while it ran.
        $attempts = 0;

        do {
            $deleted = LoginAttempt::query()
                ->where('created_at', '<', $cutoff)
                ->limit(2000)
                ->delete();

            $attempts += $deleted;
        } while ($deleted > 0);

        // Manual blocks are an operator's decision and are kept until they are
        // lifted by hand, even after they expire — the row is the record of why.
        $blocks = BlockedIp::query()
            ->where('source', BlockedIp::SOURCE_AUTO)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->subDay())
            ->delete();

        $this->info("Pruned {$attempts} login attempts older than {$days} days and {$blocks} expired auto-blocks.");

        return self::SUCCESS;
    }
}
