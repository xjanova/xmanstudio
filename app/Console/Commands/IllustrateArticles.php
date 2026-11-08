<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\Articles\ArticleImages;
use App\Services\Articles\ArticleWriter;
use Illuminate\Console\Command;

/**
 * Draw a cover and illustrations for articles that have none (docs/ARTICLES.md).
 * New articles get theirs while being written; this is for the ones before, or a redo.
 */
class IllustrateArticles extends Command
{
    protected $signature = 'articles:illustrate
        {slug? : One article}
        {--missing : Every article without a cover or with fewer than two pictures}
        {--force : Draw again even when the article already has pictures}';

    protected $description = 'Draw a cover and illustrations for knowledge-base articles';

    public function handle(ArticleWriter $writer): int
    {
        if (! $this->argument('slug') && ! $this->option('missing')) {
            $this->error('Name an article (slug) or pass --missing.');

            return self::INVALID;
        }

        $articles = $this->argument('slug')
            ? Article::where('slug', $this->argument('slug'))->get()
            : Article::orderBy('id')->get();

        if ($articles->isEmpty()) {
            $this->warn('No article to illustrate.');

            return self::SUCCESS;
        }

        $failed = 0;
        foreach ($articles as $article) {
            $figures = preg_match_all('/^!\[/mu', (string) $article->body_markdown);
            if (! $this->option('force') && $article->cover_image && $figures >= ArticleImages::MIN_INLINE) {
                $this->line('· ' . $article->slug . ' already has a cover and ' . $figures . ' pictures (--force to redo)');

                continue;
            }

            try {
                $r = $writer->illustrateExisting($article, (bool) $this->option('force'));
                $this->info('✓ ' . $article->slug . ': ' . $r['drawn'] . ' drawn' . ($r['failed'] ? ', ' . $r['failed'] . ' failed (see the log)' : ''));
            } catch (\Throwable $e) {
                $failed++;
                $this->error('✗ ' . $article->slug . ': ' . $e->getMessage());
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
