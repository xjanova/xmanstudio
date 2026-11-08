<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\ArticleFacebookPoster;
use App\Services\FacebookPageException;
use Illuminate\Console\Command;

/**
 * Share knowledge-base articles to the company Facebook page by hand: one article by slug (again,
 * with --force), or every live article the page has not had yet (--pending, oldest first).
 */
class ArticlesFacebookCommand extends Command
{
    protected $signature = 'articles:facebook
        {slug? : slug ของบทความที่จะโพสต์}
        {--pending : โพสต์ทุกบทความที่ขึ้นเว็บแล้วแต่ยังไม่เคยโพสต์ลงเพจ}
        {--limit=5 : จำนวนสูงสุดต่อรอบเมื่อใช้ --pending}
        {--force : โพสต์ใหม่แม้เคยโพสต์ไปแล้ว (เกิดโพสต์ที่สองบนเพจ)}';

    protected $description = 'Share knowledge-base articles to the company Facebook page';

    public function handle(ArticleFacebookPoster $poster): int
    {
        $slug = (string) $this->argument('slug');

        if ($slug === '' && ! $this->option('pending')) {
            $this->error('ระบุ slug ของบทความ หรือใช้ --pending เพื่อโพสต์ทุกบทความที่ยังไม่ได้โพสต์');

            return self::INVALID;
        }

        if ($slug !== '') {
            $article = Article::where('slug', $slug)->first();
            if ($article === null) {
                $this->error("ไม่พบบทความ slug \"{$slug}\"");

                return self::FAILURE;
            }
            $articles = collect([$article]);
        } else {
            $articles = Article::published()->whereNull('facebook_post_id')
                ->orderBy('published_at')->limit(max(1, (int) $this->option('limit')))->get();
            if ($articles->isEmpty()) {
                $this->info('ไม่มีบทความที่ค้างโพสต์');

                return self::SUCCESS;
            }
        }

        $failed = 0;
        foreach ($articles as $article) {
            try {
                $result = $poster->post($article, (bool) $this->option('force'));
                $this->line(($result['status'] === 'already' ? 'เคยโพสต์แล้ว (ใช้ --force เพื่อโพสต์ซ้ำ): ' : 'โพสต์แล้ว: ')
                    . $article->title . ' → ' . $result['url']);
            } catch (FacebookPageException $e) {
                $failed++;
                $this->error('ไม่สำเร็จ: ' . $article->title . ' — ' . $e->getMessage());
                if ($e->needsReconnect) {
                    break; // every other article would fail the same way
                }
            }
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
