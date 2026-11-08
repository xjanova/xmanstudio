<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A topic the article writer has already dealt with (written, skipped or failed). */
class ArticleTopic extends Model
{
    protected $fillable = ['topic_key', 'topic', 'source', 'decision', 'reason', 'article_id'];
}
