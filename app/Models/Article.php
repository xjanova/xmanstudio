<?php

namespace App\Models;

use App\Events\ArticlePublished;
use App\Http\Controllers\SitemapController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A knowledge-base article on /blog (see docs/ARTICLES.md).
 *
 * @property string $slug
 * @property string $title
 * @property ?string $excerpt
 * @property string $body
 * @property string $status
 * @property ?Carbon $published_at
 */
class Article extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    /** Category key => [Thai, English]. The writer picks one of these keys, nothing else. */
    public const CATEGORIES = [
        'ai' => ['AI และ Machine Learning', 'AI & Machine Learning'],
        'web' => ['เว็บไซต์และแอป', 'Web & Apps'],
        'security' => ['ความปลอดภัยไซเบอร์', 'Cybersecurity'],
        'blockchain' => ['Blockchain และคริปโต', 'Blockchain & Crypto'],
        'infra' => ['เซิร์ฟเวอร์ คลาวด์ และเน็ตเวิร์ก', 'Servers, Cloud & Network'],
        'business' => ['ไอทีสำหรับธุรกิจ', 'IT for Business'],
        'howto' => ['สอนใช้งาน', 'How-to'],
        'it' => ['ข่าวและเทรนด์ไอที', 'IT News & Trends'],
    ];

    protected $fillable = [
        'slug', 'title', 'excerpt', 'body', 'body_markdown', 'category', 'tags', 'focus_keyword', 'cover_image',
        'status', 'published_at', 'source', 'source_topic', 'related', 'ai_model',
        'word_count', 'reading_minutes', 'facebook_post_id', 'facebook_posted_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'related' => 'array',
        'published_at' => 'datetime',
        'facebook_posted_at' => 'datetime',
        'word_count' => 'integer',
        'reading_minutes' => 'integer',
        'views' => 'integer',
        'rating_sum' => 'integer',
        'rating_count' => 'integer',
    ];

    protected static function booted(): void
    {
        // Fire ArticlePublished exactly once per publication: on the save that turns the
        // status to "published" (created as published, or a draft switched over).
        // (Not one "saved" listener: wasRecentlyCreated stays true for the life of the instance,
        // so every later save of a just-created article would fire again.)
        static::created(function (Article $article) {
            if ($article->status === self::STATUS_PUBLISHED) {
                ArticlePublished::dispatch($article);
            }
        });

        static::updated(function (Article $article) {
            if ($article->status === self::STATUS_PUBLISHED && $article->wasChanged('status')) {
                ArticlePublished::dispatch($article);
            }
        });

        static::saved(fn () => SitemapController::forget());

        static::deleted(fn () => SitemapController::forget());
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(ArticleRating::class);
    }

    /** Live on the site: published, and its publish time has come. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function url(): string
    {
        return route('articles.show', $this->slug);
    }

    /** Absolute URL of the cover: the uploaded picture, else one drawn from the title. */
    public function coverUrl(): string
    {
        if (! $this->cover_image) {
            // ?v= changes when the drawing does: Cloudflare and Facebook keep the old picture for a day.
            return route('articles.cover', $this->slug) . '?v=2';
        }

        if (str_starts_with($this->cover_image, 'http')) {
            return $this->cover_image;
        }

        return url(Storage::disk('public')->url($this->cover_image));
    }

    public function categoryLabel(string $lang = 'th'): string
    {
        $pair = self::CATEGORIES[$this->category] ?? self::CATEGORIES['it'];

        return $lang === 'en' ? $pair[1] : $pair[0];
    }

    public function ratingAverage(): float
    {
        return $this->rating_count > 0 ? round($this->rating_sum / $this->rating_count, 1) : 0.0;
    }
}
