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
| `facebook_app_id`, `facebook_app_secret` (encrypted) | optional: lets a pasted short-lived user token be traded for a permanent page token, and lets "test" read the token's lifetime and scopes |
| `facebook_autopost_articles` | the on/off switch (default on) |

Connect or reconnect:

- Admin → ตั้งค่า → **Facebook เพจ** → **เชื่อมเพจด้วย Facebook** (the easy way): `/admin/facebook-page/connect`
  sends the admin to XmanApp's login dialog with the three scopes and a single-use `state` kept in
  the session; `/admin/facebook-page/callback` checks the state, trades the code for a user token
  (secret in the POST body), and hands it to `connect()` — long-lived exchange, `/me/accounts`, a
  page token that never expires. Needs the app id + secret saved first, and **once**, in XmanApp →
  Facebook Login → Settings → Valid OAuth Redirect URIs: `https://xman4289.com/admin/facebook-page/callback`
  (plus `xman4289.com` in Settings → Basic → App domains). A cancelled dialog changes nothing.
- Or paste a User Token (Graph API
  Explorer, app XmanApp, scopes `pages_show_list`, `pages_read_engagement`, `pages_manage_posts`) or
  a Page Token.
- Server: `... | php artisan facebook:page-token --page=106036940932060` (token on stdin — never as an
  argument, `ps` would show it), or `--file=`. `php artisan facebook:page-token --check` tests it.

## Manual posting

- `php artisan articles:facebook {slug}` — `--force` posts a second time, `--pending` posts every
  live article the page has not had yet (`--limit=5`).
- `POST /admin/articles/{article}/facebook` (`admin.articles.facebook`, add `force=1` to repost) — for
  a "post to Facebook" button on the article admin screen.
