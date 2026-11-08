# Company Facebook page — sharing new articles

Every knowledge-base article that goes live on `/blog` is shared once to the company page
**XmanEnterprise** (page id `106036940932060`, category "IT company", website xman4289.com).

## How it flows

1. `Article` turns `published` → `App\Events\ArticlePublished` (after commit).
2. `App\Listeners\PostArticleToFacebook` (queued, auto-discovered) — skipped when the admin switch
   is off, the page is not connected, or the article already has `facebook_post_id`. An article
   scheduled for later is delayed until its `published_at`.
3. `App\Services\ArticleFacebookPoster` takes a cache lock, re-checks `facebook_post_id`, asks
   Facebook to re-scrape the URL's og: tags, posts a link post, and stores
   `facebook_post_id` / `facebook_posted_at` (quietly — no model events).
4. Caption: `App\Support\FacebookArticleCaption` — title, teaser (excerpt or first paragraph), one
   tip (first list item), the link, 3–5 hashtags ending in `#XMANStudio`. No bait, no price.
5. Failure: busy / rate limit / 5xx → retried (1, 5, 30 min). Token or permission problems → no
   retry; one Telegram card (`SystemAlerts::facebookPostFailed`, category `system`).

## Token — same Meta app as the fortune pages

The page token comes from **XmanApp** (`664172615253513`), the app the แม่หมอจันทราพยากรณ์ page
uses in Thaiprompt-Affiliate. A page token read from `/me/accounts` with a *long-lived* user token
never expires; only the page token is stored here, encrypted (`Setting::$encryptedKeys`):

| Setting key | |
|---|---|
| `facebook_page_id`, `facebook_page_name` | the page |
| `facebook_page_access_token` | encrypted, write-only in the UI |
| `facebook_app_id`, `facebook_app_secret` (encrypted) | optional but recommended: a pasted short-lived token — user **or page** — is traded (`fb_exchange_token`) for a page token that never expires, and "test" reads the token's lifetime and scopes |
| `facebook_autopost_articles` | the on/off switch (default on) |

Connect or reconnect:

- Admin → ตั้งค่า → **Facebook เพจ** (`/admin/facebook-page`): paste a User Token (Graph API
  Explorer, app XmanApp, scopes `pages_show_list`, `pages_read_engagement`, `pages_manage_posts`) or
  a Page Token. A page token straight from Explorer lives about an hour; with the app id/secret saved
  it is traded for one that never expires (kept only if it still answers as the same page; a token
  from a different app cannot be traded and is kept with a warning showing when it expires).
- Server: `... | php artisan facebook:page-token --page=106036940932060` (token on stdin — never as an
  argument, `ps` would show it), or `--file=`. `php artisan facebook:page-token --check` tests it.

## Manual posting

- `php artisan articles:facebook {slug}` — `--force` posts a second time, `--pending` posts every
  live article the page has not had yet (`--limit=5`).
- `POST /admin/articles/{article}/facebook` (`admin.articles.facebook`, add `force=1` to repost) — for
  a "post to Facebook" button on the article admin screen.
