<?php

namespace App\Console\Commands;

use App\Models\BlockedIp;
use Illuminate\Console\Command;

/**
 * The way back in.
 *
 * The admin panel can lift a block, but only if you can reach the admin panel
 * — and the one case where that matters most is the one where you cannot,
 * because the address you are sitting behind is the one on the list. This runs
 * from the server, where the block does not apply.
 */
class SecurityUnblockCommand extends Command
{
    protected $signature = 'security:unblock
                            {ip? : The address to release}
                            {--all : Release every blocked address}
                            {--list : Show the current block list and exit}';

    protected $description = 'Release an IP address from the login block list';

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listBlocks();
        }

        if ($this->option('all')) {
            $count = BlockedIp::query()->count();

            if ($count === 0) {
                $this->info('No addresses are blocked.');

                return self::SUCCESS;
            }

            // Every blocked address at once includes whatever is currently
            // attacking the site, so this asks first.
            if (! $this->confirm("Release all {$count} blocked addresses?", false)) {
                $this->comment('Cancelled.');

                return self::SUCCESS;
            }

            BlockedIp::query()->get()->each(fn (BlockedIp $b) => BlockedIp::unblock($b->ip));

            $this->info("Released {$count} addresses.");

            return self::SUCCESS;
        }

        $ip = $this->argument('ip');

        if (! $ip) {
            $this->error('Give an IP address, or --all, or --list.');

            return self::FAILURE;
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->error("'{$ip}' is not an IP address.");

            return self::FAILURE;
        }

        if (! BlockedIp::query()->where('ip', $ip)->exists()) {
            $this->warn("{$ip} is not on the block list.");

            return self::SUCCESS;
        }

        BlockedIp::unblock($ip);

        $this->info("Released {$ip}.");

        return self::SUCCESS;
    }

    private function listBlocks(): int
    {
        $blocks = BlockedIp::query()->orderByDesc('updated_at')->get();

        if ($blocks->isEmpty()) {
            $this->info('No addresses are blocked.');

            return self::SUCCESS;
        }

        $this->table(
            ['IP', 'Source', 'Expires', 'Hits', 'Reason'],
            $blocks->map(fn (BlockedIp $b) => [
                $b->ip,
                $b->source,
                $b->expires_at?->format('Y-m-d H:i') ?? 'never',
                $b->hits,
                mb_strimwidth((string) $b->reason, 0, 48, '…'),
            ])->all()
        );

        return self::SUCCESS;
    }
}
