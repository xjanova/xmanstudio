<?php

namespace App\Events;

use App\Models\Article;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An article went live on /blog — fired once, the moment its status becomes "published"
 * (Article::booted). Listeners: the Facebook page poster shares it to the company page.
 */
class ArticlePublished implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Article $article) {}
}
