<?php

namespace App\Console\Commands;

use App\Models\BugReport;
use App\Models\Setting;
use Illuminate\Console\Command;

class BugReportCleanupCommand extends Command
{
    protected $signature = 'bugreports:cleanup';

    protected $description = 'ลบ Bug Reports เก่าอัตโนมัติตามจำนวนวันที่ตั้งค่าไว้';

    public function handle(): int
    {
        $days = (int) Setting::getValue('bug_report_auto_delete_days', 0);

        if ($days <= 0) {
            $this->info('Auto-delete is disabled (days = 0).');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);

        $reports = BugReport::where('created_at', '<', $cutoff)->get();

        if ($reports->isEmpty()) {
            $this->info("No bug reports older than {$days} days found.");

            return self::SUCCESS;
        }

        $count = 0;
        foreach ($reports as $report) {
            $report->attachments->each(fn ($a) => $a->deleteFile());
            $report->forceDelete();
            $count++;
        }

        $this->info("Deleted {$count} bug reports older than {$days} days.");

        return self::SUCCESS;
    }
}
