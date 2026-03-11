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
            $this->info('DRY RUN - no changes will be made');
        }

        // Find all device users
        $deviceUsers = User::where('email', 'like', '%@tping.device')->get();

        if ($deviceUsers->isEmpty()) {
            $this->info('No device users found.');

            return self::SUCCESS;
        }

        $this->info('Found ' . $deviceUsers->count() . ' device user(s)');

        $reassigned = 0;

        foreach ($deviceUsers as $deviceUser) {
            $this->line('');
            $this->info('Processing: ' . $deviceUser->name . ' (' . $deviceUser->email . ')');

            // Extract machine_id hash from device email (device-{hash}@tping.device)
            $emailHash = str_replace(['device-', '@tping.device'], '', $deviceUser->email);
            $this->line('  Machine hash: ' . $emailHash);

            // Strategy 1: Find license linked directly to this device user
            $license = LicenseKey::where('user_id', $deviceUser->id)
                ->with('order')
                ->first();

            // Strategy 2: Find license via product_id from workflows owned by this device user
            if (! $license) {
                $workflow = ProductWorkflow::where('user_id', $deviceUser->id)->first();
                if ($workflow && $workflow->product_id) {
                    // Find any active license for this product that has an order
                    $license = LicenseKey::where('product_id', $workflow->product_id)
                        ->whereNotNull('order_id')
                        ->with('order')
                        ->first();
                    if ($license) {
                        $this->line('  Found license via workflow product_id: #' . $license->id);
                    }
                }
            }

            // Strategy 3: Find license via data profile product_id
            if (! $license) {
                $profile = ProductDataProfile::where('user_id', $deviceUser->id)->first();
                if ($profile && $profile->product_id) {
                    $license = LicenseKey::where('product_id', $profile->product_id)
                        ->whereNotNull('order_id')
                        ->with('order')
                        ->first();
                    if ($license) {
                        $this->line('  Found license via profile product_id: #' . $license->id);
                    }
                }
            }

            if (! $license) {
                $this->warn('  No license found, skipping.');

                continue;
            }

            // Find the real user from the order
            $realUser = null;

            if ($license->order && $license->order->user_id) {
                $realUser = User::find($license->order->user_id);
            }

            // Fallback: check license user_id directly (if it's a real user)
            if (! $realUser && $license->user_id && $license->user_id !== $deviceUser->id) {
                $candidate = User::find($license->user_id);
                if ($candidate && ! str_ends_with($candidate->email, '@tping.device')) {
                    $realUser = $candidate;
                }
            }

            if (! $realUser) {
                $this->warn('  License #' . $license->id . ' has no real user, skipping.');

                continue;
            }

            if ($realUser->id === $deviceUser->id) {
                $this->line('  Already assigned to real user, skipping.');

                continue;
            }

            $this->info('  Real user: ' . $realUser->name . ' (' . $realUser->email . ') #' . $realUser->id);

            // Count data to reassign
            $workflowCount = ProductWorkflow::where('user_id', $deviceUser->id)->count();
            $profileCount = ProductDataProfile::where('user_id', $deviceUser->id)->count();

            $this->line('  Workflows: ' . $workflowCount . ', Data Profiles: ' . $profileCount);

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

                $this->info('  Reassigned ' . $workflowCount . ' workflows + ' . $profileCount . ' profiles to ' . $realUser->name);
            } else {
                $this->info('  Would reassign ' . $workflowCount . ' workflows + ' . $profileCount . ' profiles to ' . $realUser->name);
            }

            $reassigned += $workflowCount + $profileCount;
        }

        $this->line('');
        if ($dryRun) {
            $this->info('Would reassign ' . $reassigned . ' total items.');
        } else {
            $this->info('Done! Reassigned ' . $reassigned . ' total items.');
        }

        return self::SUCCESS;
    }
}
