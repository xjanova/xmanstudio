# Knowledge base articles (`/blog`)

IT articles that teach first and sell softly. They are written by the site's own AI (the same
provider Nova uses, `App\Services\AiChatService`) from what Thai people are searching for today,
and they link only to things we really sell. Readers rate each article with 1–5 stars.

## Pieces

| Piece | File |
|---|---|
| Tables `articles`, `article_ratings`, `article_topics` | `database/migrations/2026_11_08_100000_create_articles_tables.php` |
| Article model, categories, `ArticlePublished` once per publication | `app/Models/Article.php`, `app/Events/ArticlePublished.php` |
| Markdown → safe HTML (raw HTML stripped, `javascript:` links dropped, h2/h3 ids, external links `nofollow`) | `app/Support/ArticleHtml.php` |
| Public pages: list, article, stars, RSS | `app/Http/Controllers/ArticleController.php`, `resources/views/articles/` |
| Pictures: a cover and up to two figures drawn with OpenAI's image API | `app/Services/Articles/ArticleImages.php`, `articles:illustrate` |
| Title card when an article has no cover | `OgImageController::article` → `/blog/{slug}/cover.png` |
| Topic sources (Google Trends TH daily RSS + Google News TH Technology RSS) | `app/Services/Articles/TopicSources.php` |
| What we sell, with real prices, for the writer to link | `app/Services/Articles/Catalogue.php` |
| The editor (picks topics) and the writer (writes one article) | `app/Services/Articles/ArticleWriter.php` |
| Command / schedule / background job | `app/Console/Commands/GenerateArticles.php`, `routes/console.php`, `app/Jobs/GenerateArticlesJob.php` |
| Admin screen (list, edit, settings, "write now") | `app/Http/Controllers/Admin/ArticleController.php`, `resources/views/admin/articles/` |
| Sitemap (built live, cached 30 min, cleared when an article changes) | `app/Http/Controllers/SitemapController.php` |

## How an article is made

1. **Topics.** `TopicSources::all()` reads today's Google Trends (geo=TH) and Thai tech news.
   Topics already written or skipped (`article_topics`) are filtered out, so a trend is never
   written twice; a topic whose writing failed may be offered again.
2. **Editor.** One AI call picks the topics that are IT, or that can honestly be turned into an IT
   lesson, and that fit something in `Catalogue`. Sport, celebrities, politics and disasters are
   skipped. Every decision (written / skipped / failed, with the reason) is stored in `article_topics`.
3. **Writer.** One long AI call (`forLongOutput(12000, 300)`) returns a small header
   (`TITLE / SLUG / EXCERPT / CATEGORY / FOCUS / TAGS / IMAGE`), `---`, then Markdown. The prompt asks for:
   teach first, open with a real situation, a conversational tone, a checklist the reader can act on,
   no invented numbers or facts, a FAQ section, one soft "how we can help" section near the end,
   links only from the catalogue it was given, no H1, and two `[[IMG: English subject | Thai alt]]`
   placeholders on their own lines under `##` headings.
4. **Pictures.** See below. Never blocks the article.
5. **Checks.** An answer under `ArticleWriter::MIN_TEXT_CHARS` characters of text is refused, not
   published. The slug is made unique. The real failure reason is kept and shown on the admin screen.
6. **Publish.** When `articles_auto_publish` is on it is published, going live at `--publish-at`
   (a future `published_at` hides it from `/blog`, the sitemap and Facebook until then); otherwise it is saved as a draft.

## Pictures

`ArticleImages` draws on the **same OpenAI key** that writes the articles (`openai_api_key`, or
`ai_openai_key`), with the cheapest model and quality: `gpt-image-1-mini`, `low`, 1536×1024 WebP —
about 400 output image tokens, well under ฿1 a picture (cover + 2 figures per article).

- One house style for every picture (navy, purple/cyan neon, flat 3D) and **no text in pictures**:
  image models misspell, and Thai comes out as noise. The Thai goes into the `alt`.
- Files: `storage/app/public/articles/{slug}/cover-xxxxxx.webp`, `figure-1-…`, `figure-2-…`.
  Deleting an article in admin deletes the folder.
- Only a real WebP of sane size is stored. A refusal (billing, safety, model access) is logged with
  the provider's own message; that picture is skipped, the placeholder removed, and the cover falls back
  to the title card. Any `[[IMG:` text the writer left mid-paragraph is removed too.
- Settings: `articles_images_enabled` (admin checkbox, default on), `articles_image_model`,
  `articles_image_quality` (low / medium / high).
- Older articles: `php artisan articles:illustrate {slug}` or `--missing` (one AI call picks the
  subjects under the article's own headings, then draws); `--force` draws again.

## Schedule and settings

- Two articles a day, live when Thai readers read IT the most: lunch break and evening.
  `articles:generate --scheduled --publish-at=12:00` runs at 10:30 and `--publish-at=20:00` at 18:30
  (Asia/Bangkok), in the background, never two at once. Writing and drawing takes a few minutes, so
  it is written ahead and goes live on the hour; a run that finishes late goes live at once.
- `--scheduled` does nothing when `articles_auto_enabled` is off.
- Settings (admin → บทความ): `articles_auto_enabled` (default on), `articles_auto_publish` (default on),
  `articles_per_run` (default 1, max 5).
- Manual: `php artisan articles:generate --count=1 [--draft] [--topic="หัวข้อที่อยากให้เขียน"] [--publish-at=HH:MM]`.
- The admin "สั่งเขียนตอนนี้" form (optional topic) queues `GenerateArticlesJob`; it refuses while a run is going,
  and its result lands in the cache key `articles:last_run`.

**Keep it slow.** Google's scaled-content policy punishes many thin AI pages. One article per run,
twice a day, is the intended pace; raise it only together with someone reading the drafts.

## Stars

`POST /blog/{slug}/rate` `{stars: 1..5}` — one vote per reader, changeable. A signed-in reader is
`user:{id}`; a guest is an HMAC of IP + user agent (`voter_hash`). Totals live on the article
(`rating_sum`, `rating_count`) and are recounted in a transaction on every vote. Throttled 20/min.

## SEO pieces that came with it

- `public_html/robots.txt` and `public_html/sitemap.xml` must **not** exist: as static files in the
  web root they shadow the `/robots.txt` and `/sitemap.xml` routes. Admin → SEO → "สร้าง Sitemap ใหม่"
  deletes them if they reappear and clears the sitemap cache.
- `/robots.txt` uses the admin's text and always ends with the `Sitemap:` line.
- Each article page carries `BlogPosting` + `BreadcrumbList` JSON-LD, the cover as `og:image`,
  and a table of contents from its h2/h3.

## Facebook

Posting new articles to the company page listens to `ArticlePublished` and owns the
`facebook_post_id` / `facebook_posted_at` columns; it is built separately (see its own doc once merged).
