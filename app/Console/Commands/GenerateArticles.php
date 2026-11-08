<?php

namespace App\Console\Commands;

use App\Services\Articles\ArticleWriter;
use Illuminate\Console\Command;

/**
 * Writes knowledge-base articles from today's Google Trends / tech news and our catalogue.
 * Scheduled twice a day (routes/console.php); admins can also run it from /admin/articles.
 */
class GenerateArticles extends Command
{
    protected $signature = 'articles:generate
        {--count= : How many articles (default: the admin setting, 1)}
        {--draft : Keep them as drafts instead of publishing}
        {--topic= : Write about this topic instead of picking one}
        {--scheduled : Run by the scheduler: does nothing while the admin switch is off}
        {--publish-at= : Go live at this Bangkok time today (HH:MM); now if already past}';

    protected $description = 'Write IT articles for /blog from Google Trends, tech news and our catalogue';

    public function handle(ArticleWriter $writer): int
    {
        if ($this->option('scheduled') && ! ArticleWriter::autoEnabled()) {
            $this->line('Automatic articles are switched off (admin → บทความ).');

            return self::SUCCESS;
        }

        $count = $this->option('count') !== null ? max(1, min(5, (int) $this->option('count'))) : ArticleWriter::perRun();
        $publish = $this->option('draft') ? false : null;

        $publishAt = null;
        if ($at = $this->option('publish-at')) {
            if (! preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $at, $m)) {
                $this->error('--publish-at must be HH:MM, e.g. 12:00');

                return self::INVALID;
            }
            // Stored in the app's timezone; the hour is the readers' (Bangkok) hour.
            $publishAt = now('Asia/Bangkok')->setTime((int) $m[1], (int) $m[2])->setTimezone(config('app.timezone'));
        }

        try {
            $results = $writer->run($count, $publish, $this->option('topic') ?: null, $publishAt);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($results as $r) {
            if ($r['ok']) {
                $live = $r['article']->published_at?->isFuture() ? ' live at ' . $r['article']->published_at->timezone('Asia/Bangkok')->format('H:i') : '';
                $this->info('✓ ' . $r['article']->title . ' → ' . $r['article']->url() . ' [' . $r['article']->status . $live . ']');
            } else {
                $this->error('✗ ' . $r['topic'] . ': ' . $r['error']);
            }
        }

        return collect($results)->contains('ok', true) ? self::SUCCESS : self::FAILURE;
    }
}
