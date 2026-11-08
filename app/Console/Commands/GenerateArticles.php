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
        {--scheduled : Run by the scheduler: does nothing while the admin switch is off}';

    protected $description = 'Write IT articles for /blog from Google Trends, tech news and our catalogue';

    public function handle(ArticleWriter $writer): int
    {
        if ($this->option('scheduled') && ! ArticleWriter::autoEnabled()) {
            $this->line('Automatic articles are switched off (admin → บทความ).');

            return self::SUCCESS;
        }

        $count = $this->option('count') !== null ? max(1, min(5, (int) $this->option('count'))) : ArticleWriter::perRun();
        $publish = $this->option('draft') ? false : null;

        try {
            $results = $writer->run($count, $publish, $this->option('topic') ?: null);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($results as $r) {
            if ($r['ok']) {
                $this->info('✓ ' . $r['article']->title . ' → ' . $r['article']->url() . ' [' . $r['article']->status . ']');
            } else {
                $this->error('✗ ' . $r['topic'] . ': ' . $r['error']);
            }
        }

        return collect($results)->contains('ok', true) ? self::SUCCESS : self::FAILURE;
    }
}
