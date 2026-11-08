<?php

namespace App\Services;

use App\Models\Article;
use App\Support\FacebookArticleCaption;
use Illuminate\Support\Facades\Cache;

/**
 * Shares one knowledge-base article to the company Facebook page — once.
 *
 * "Once" is the article's facebook_post_id: set the moment Facebook answers, checked under a lock,
 * so the queued listener, a retry, the artisan command and the admin button can all be pressed at
 * the same time and the page still gets one post. Posting again on purpose takes $force.
 */
class ArticleFacebookPoster
{
    public function __construct(private readonly FacebookPageService $facebook) {}

    /**
     * @return array{status: string, post_id: string, url: string} status is "posted" or "already"
     *
     * @throws FacebookPageException
     */
    public function post(Article $article, bool $force = false): array
    {
        if (! $this->facebook->isConfigured()) {
            throw new FacebookPageException('ยังไม่ได้เชื่อมเพจ Facebook — เชื่อมที่ ' . FacebookPageService::SETTINGS_SCREEN . ' ก่อน', needsReconnect: true);
        }

        $lock = Cache::lock('facebook-article-post:' . $article->getKey(), 120);
        if (! $lock->get()) {
            throw new FacebookPageException('บทความนี้กำลังถูกโพสต์อยู่ในอีกงานหนึ่ง — รอสักครู่แล้วดูที่หน้าเพจ', retryable: true);
        }

        try {
            $article->refresh();

            if (filled($article->facebook_post_id) && ! $force) {
                return ['status' => 'already', 'post_id' => (string) $article->facebook_post_id, 'url' => FacebookPageService::postUrl((string) $article->facebook_post_id)];
            }

            if (! $article->isLive()) {
                throw new FacebookPageException('บทความ "' . $article->title . '" ยังไม่ขึ้นบนเว็บ (สถานะ ' . $article->status
                    . ($article->published_at?->isFuture() ? ', ตั้งเวลาเผยแพร่ ' . $article->published_at->timezone('Asia/Bangkok')->format('d/m/Y H:i') : '')
                    . ') — ลิงก์ยังเปิดไม่ได้ จึงยังไม่โพสต์');
            }

            $link = $article->url();
            $this->facebook->refreshLinkPreview($link);
            $postId = $this->facebook->postLink(FacebookArticleCaption::for($article), $link);

            // Quietly: this is bookkeeping, not an edit — no "saved" listeners, no publish event.
            $article->forceFill(['facebook_post_id' => $postId, 'facebook_posted_at' => now()])->saveQuietly();

            return ['status' => 'posted', 'post_id' => $postId, 'url' => FacebookPageService::postUrl($postId)];
        } finally {
            $lock->release();
        }
    }
}
