<?php

namespace App\Listeners;

use App\Events\ArticlePublished;
use App\Models\Article;
use App\Services\ArticleFacebookPoster;
use App\Services\FacebookPageException;
use App\Services\FacebookPageService;
use App\Support\Alerts\SystemAlerts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * A new knowledge-base article went live → share it on the company Facebook page.
 *
 * Queued, so publishing never waits on Facebook. An article scheduled for later is held until its
 * publish time (Facebook would otherwise scrape a 404). Facebook being busy or rate-limiting is
 * retried; anything a person has to fix (token expired, permission missing) is not — the owner
 * gets one Telegram card saying exactly that, and the article can be posted later by hand
 * (admin button or `php artisan articles:facebook {slug}`).
 */
class PostArticleToFacebook implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 4;

    /** Seconds before each retry when Facebook is busy: 1 min, 5 min, 30 min. */
    public array $backoff = [60, 300, 1800];

    /** The article was deleted before the job ran — nothing to share. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        private readonly ArticleFacebookPoster $poster,
        private readonly FacebookPageService $facebook,
    ) {}

    /** Do not even queue a job that cannot post. */
    public function shouldQueue(ArticlePublished $event): bool
    {
        return $this->facebook->autopostEnabled()
            && $this->facebook->isConfigured()
            && blank($event->article->facebook_post_id);
    }

    /** Scheduled for later: run when it goes live, not before. */
    public function withDelay(ArticlePublished $event): int
    {
        return self::secondsUntilLive($event->article);
    }

    public function handle(ArticlePublished $event): void
    {
        $article = $event->article->fresh();

        if ($article === null
            || filled($article->facebook_post_id)
            || $article->status !== Article::STATUS_PUBLISHED
            || ! $this->facebook->autopostEnabled()) {
            return; // posted meanwhile, taken back to draft, or the switch was turned off
        }

        if (! $article->isLive()) {
            $this->release(max(60, self::secondsUntilLive($article))); // publish time moved later

            return;
        }

        try {
            $this->poster->post($article);
        } catch (FacebookPageException $e) {
            if ($e->retryable && $this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 1800);

                return;
            }

            Log::warning('facebook: sharing an article failed', ['article' => $article->slug, 'error' => $e->getMessage()]);
            SystemAlerts::facebookPostFailed($article, $e->getMessage(), $e->needsReconnect);
        }
    }

    /** The job itself died (an unexpected exception, or out of attempts while waiting). */
    public function failed(ArticlePublished $event, Throwable $e): void
    {
        try {
            $article = $event->article->fresh() ?? $event->article;
            SystemAlerts::facebookPostFailed($article, 'งานเบื้องหลังล้มเหลว: ' . $e->getMessage(), false);
        } catch (Throwable) {
        }
    }

    private static function secondsUntilLive(Article $article): int
    {
        $at = $article->published_at;

        return $at !== null && $at->isFuture() ? $at->getTimestamp() - time() + 30 : 0;
    }
}
