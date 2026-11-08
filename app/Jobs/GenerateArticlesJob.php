<?php

namespace App\Jobs;

use App\Services\Articles\ArticleWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * "Write now" from /admin/articles: an article takes minutes, so it runs on the queue and
 * leaves what happened in the cache for the admin page to show.
 */
class GenerateArticlesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const LAST_RUN_KEY = 'articles:last_run';

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public int $count = 1, public ?string $topic = null, public ?bool $publish = null) {}

    public function handle(ArticleWriter $writer): void
    {
        Cache::put(self::LAST_RUN_KEY, ['state' => 'running', 'at' => now()->toIso8601String(), 'results' => []], now()->addDay());

        try {
            $results = $writer->run($this->count, $this->publish, $this->topic);
            $summary = array_map(fn ($r) => $r['ok']
                ? ['ok' => true, 'topic' => $r['topic'], 'title' => $r['article']->title, 'id' => $r['article']->id, 'status' => $r['article']->status]
                : ['ok' => false, 'topic' => $r['topic'], 'error' => $r['error']], $results);

            Cache::put(self::LAST_RUN_KEY, ['state' => 'done', 'at' => now()->toIso8601String(), 'results' => $summary], now()->addWeek());
        } catch (\Throwable $e) {
            Cache::put(self::LAST_RUN_KEY, ['state' => 'failed', 'at' => now()->toIso8601String(), 'error' => $e->getMessage(), 'results' => []], now()->addWeek());
        }
    }
}
