<?php

namespace App\Console\Commands;

use App\Models\LicenseKey;
use App\Models\ProductDataProfile;
use App\Models\ProductWorkflow;
use App\Models\User;
use Illuminate\Console\Command;

class ReassignCloudDataCommand extends Command
{
    protected $signature = 'cloud:reassign-data {--dry-run : Show what would be changed without making changes}';

    protected $description = 'Reassign cloud data (workflows & data profiles) from device users to the real license holders';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN — no changes will be made');
        }

        // Find all device users
        $deviceUsers = User::where('email', 'like', '%@tping.device')->get();

        if ($deviceUsers->isEmpty()) {
            $this->info('No device users found.');

            return self::SUCCESS;
        }

        $this->info("Found {$deviceUsers->count()} device user(s)");

        $reassigned = 0;

        foreach ($deviceUsers as $deviceUser) {
            $this->line('');
            $this->info("Processing: {$deviceUser->name} ({$deviceUser->email})");

            // Find license linked to this device user
            $license = LicenseKey::where('user_id', $deviceUser->id)
                ->with('order')
                ->first();

            if (! $license) {
                // Try to find via license activities or other means
                $this->warn("  No license found for device user #{$deviceUser->id}, skipping.");

                continue;
            }

            // Find the real user from the order
            $realUser = null;

            if ($license->order && $license->order->user_id) {
                $realUser = User::find($license->order->user_id);
            }

            if (! $realUser) {
                $this->warn("  License #{$license->id} has no order with a real user, skipping.");

                continue;
            }

            if ($realUser->id === $deviceUser->id) {
                $this->line("  Already assigned to real user, skipping.");

                continue;
            }

            $this->info("  Real user: {$realUser->name} ({$realUser->email})");

            // Count data to reassign
            $workflowCount = ProductWorkflow::where('user_id', $deviceUser->id)->count();
            $profileCount = ProductDataProfile::where('user_id', $deviceUser->id)->count();

            $this->line("  Workflows: {$workflowCount}, Data Profiles: {$profileCount}");

            if ($workflowCount === 0 && $profileCount === 0) {
                $this->line('  No cloud data to reassign.');

                continue;
            }

            if (! $dryRun) {
                // Reassign workflows
                ProductWorkflow::where('user_id', $deviceUser->id)
                    ->update(['user_id' => $realUser->id]);

                // Reassign data profiles
                ProductDataProfile::where('user_id', $deviceUser->id)
                    ->update(['user_id' => $realUser->id]);

                // Update license user_id to real user
                $license->update(['user_id' => $realUser->id]);

                $this->info("  ✅ Reassigned {$workflowCount} workflows + {$profileCount} profiles → {$realUser->name}");
            } else {
                $this->info("  Would reassign {$workflowCount} workflows + {$profileCount} profiles → {$realUser->name}");
            }

            $reassigned += $workflowCount + $profileCount;
        }

        $this->line('');
        $this->info($dryRun
            ? "Would reassign {$reassigned} total items."
            : "Done! Reassigned {$reassigned} total items."
        );

        return self::SUCCESS;
    }
}
