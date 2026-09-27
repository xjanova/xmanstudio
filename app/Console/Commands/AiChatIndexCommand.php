<?php

namespace App\Console\Commands;

use App\Services\AiChat\SiteIndex;
use Illuminate\Console\Command;

/**
 * Reads every public page of the site so the AI assistant (น้อง Nova) knows
 * what each one says — see App\Services\AiChat\SiteIndex.
 *
 * Scheduled every ten minutes with --if-stale (routes/console.php): a no-op
 * unless a deploy changed the site or the copy is six hours old, so a new or
 * edited page reaches the assistant within minutes of going live.
 */
class AiChatIndexCommand extends Command
{
    protected $signature = 'ai-chat:index {--if-stale : อ่านใหม่เฉพาะเมื่อเว็บเปลี่ยน (deploy ใหม่) หรือข้อมูลเก่ากว่า 6 ชั่วโมง}';

    protected $description = 'อ่านเนื้อหาทุกหน้าสาธารณะของเว็บ เก็บไว้ให้ AI แชท (น้อง Nova) ตอบจากหน้าเว็บจริง';

    public function handle(SiteIndex $index): int
    {
        if ($this->option('if-stale') && ! $index->isStale()) {
            $this->line('Site index is fresh (built ' . $index->builtAt()?->diffForHumans() . ').');

            return self::SUCCESS;
        }

        $started = microtime(true);
        $result = $index->build();

        $this->info(sprintf('Indexed %d pages in %.1fs.', $result['pages'], microtime(true) - $started));

        foreach ($result['skipped'] as $path => $why) {
            $this->line("  skipped {$path}: {$why}");
        }

        foreach ($result['failed'] as $path => $why) {
            $this->warn("  failed {$path}: {$why}");
        }

        return self::SUCCESS;
    }
}
