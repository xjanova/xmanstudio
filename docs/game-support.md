# Game support / donations and community

Central support portal for the GamesHub catalog, with authenticated slip submissions, manual bank review, reward tracking, comments, primary game votes and per-game star ratings. All campaigns use the owner-confirmed company account in `config/game-support.php`: SCB, บริษัท เอ็กซ์แมน เอนเตอร์ไพรส์ จำกัด, 411-148476-9. This is a bank account, not a PromptPay identifier.

## Entry points

- Public catalog/rankings: `/games-support`; project: `/games-support/{slug}`.
- Read-only aggregate JSON: `/games-support/summary.json`. Includes slug, name, goal, raised (baht), distinct approved supporters, votes, average stars and rating_count. No member IDs, bank references or slips.
- Existing admin sidebar → เกม · บริจาค / สลิป / รางวัล / ความเห็น → `/admin/game-support`.
- Uses existing XMAN ID authentication, admin role and production admin two-factor gate. All writes use Laravel web CSRF. No auth or CSRF exceptions added.

## Receiving and checking a transfer

1. Donor selects project, amount (1–1,000,000 baht, up to two decimals), transfers with their bank, checks the company recipient, then signs in to submit a JPEG/PNG/WebP slip (5 MB max, 8,000 px max per side), chosen display name and optional suggestion. Public name/amount/reward and public comment each require opt-in. Comments otherwise remain private in the donation record.
2. The pending record stores bank/reward snapshots. A pending donation does not count toward public funding or grant rewards. The donor can see their last 20 records and review/reward statuses.
3. Admin opens the protected slip, independently checks recipient, amount, transaction details and bank credit. To approve, enter the exact credited amount, bank transaction reference, both review confirmations and a donor-facing note. Reject incomplete/unmatched submissions with a note.
4. Approval makes the contribution eligible and publishes the opted-in donor/reward. Mark fulfillment with a note once the reward has actually been delivered. The default rewards are website supporter badges plus optional donor credit, not in-game power or unreleased skins.
5. An erroneous/refunded approved item can be voided with a reason. This removes its public funding/reward eligibility and preserves its history/reference. Voiding does not transfer a refund; perform any actual refund through the bank separately.

This is **manual verification**, not automated slip authenticity or bank reconciliation. No paid slip verification service, fabricated verification result or payment credential is needed. Before opening publicly, the operator should have independent access to the company's bank credit records.

## Consistency and privacy

Amounts are parsed into integer satang. Transactions lock the donation row; approval and fulfillment cannot be repeated. SHA-256 rejects the same uploaded file across all games, and the database enforces unique bank transaction references even after void. File hashing alone cannot detect edited copies; admin checks against bank credit remain necessary.

Slips use the private local disk, streamed only through authenticated admin routes with no-store and nosniff. Never place `storage/app/private/game-slips` under public storage or an asset backup. Back up it privately alongside the database and keep write permissions for Laravel. Public donor queries include only opted-in names, amounts and rewards; private comments, review notes, user IDs and transaction references are excluded.

Financial records restrict member/campaign deletion to preserve review history. Do not force-delete members with donations or cascade their financial records. Admin actions append reviewer/time/reason audit events. Campaign reward edits affect future submissions only; existing reward snapshots stay intact. Campaign slugs stay stable so public links and donation associations continue working.

## Feedback and rankings

A member has one primary vote across all games and can move it to another game. Stars are 1–5, one rating per member per game, editable without adding duplicate ratings. Donation is never required for either action or suggestions. New comments await moderation; admins can publish or hide previously published comments. Public rankings exclude categories with no entries, display rating counts, and use a deterministic tie order that is not a claim of popularity.

The catalog migration registers 29 current GamesHub entries with BREAKER goal 150,000 baht, HIVE BREACH 500,000 baht, and other goals unset. Admin can create games, pause them, change names/descriptions/goals and reward tiers. New GamesHub games also need a portal campaign, using the same stable slug. The frontend game detail links provide voting/rating/donor access for every game.

## Deployment

1. Deploy this backend before GamesHub frontend. Use the existing deployment/migration process and database backup; the migration creates only five new `game_*` tables and seeds campaigns. It does not alter checkout, Aipray donations, wallets or unrelated settings.
2. Run `php artisan migrate --force`; publish the three static assets `css/game-support.css`, `js/game-support.js`, `images/banks/scb.svg` as part of the normal repository deployment. Standard route/view caching is supported.
3. `GAME_SUPPORT_HUB_ORIGIN` defaults to `https://xgameshub.xman4289.com` and controls the aggregate JSON CORS origin. No credentialed cross-site writes are supported. Local QA overrides it to `http://127.0.0.1:3107` only in the isolated preview environment.
4. GamesHub `NEXT_PUBLIC_XMAN_STUDIO_URL` defaults to `https://xman4289.com`. Publish its campaign site after verifying this portal and summary endpoint are reachable, admin review permissions work and the company bank recipient is correct in the banking app.
5. SCB logo is the official asset from https://www.scb.co.th/getmedia/d5d9617f-8bd7-4c55-ac23-9b8e6a97b945/logo-scb-desktop.svg. Its use identifies the receiving bank; do not suggest bank endorsement.

No preview users, slips, donation totals, .env credentials or SQLite fixtures are committed. Production deployment remains separate from local validation.

## Validation

`tests/Feature/GameSupportTest.php` covers private storage, approval conditions, integer amounts, cross-game duplicate slips/references, repeated review/fulfillment, voided totals, anonymous donor privacy, role authorization, reward snapshots, stable slugs, movable votes, editable ratings, moderated/escaped comments and paused campaigns. Run with the repository's normal PHPUnit command and testing database. Do not run RefreshDatabase against live databases.

Local verification: PHP 8.3, SQLite in-memory, 14 tests / 102 assertions; Pint; route cache and Blade cache. Browser QA used an isolated local SQLite preview with a synthetic image clearly marked NO MONEY TRANSFERRED to verify upload → review → donor/reward/feedback and aggregate API behavior.

## XGamesHub back office (`/admin/gameshub`)

One admin section controls everything xgameshub.xman4289.com shows that is not baked into its static build. Sidebar → **XGamesHub · บริจาค ไอเท็ม รีวิว ความเห็น**. Tabs:

| Tab | Route | What |
| --- | --- | --- |
| ภาพรวม | `admin.gameshub.dashboard` | pending slips / reviews / comments, approved total, item codes issued and redeemed, live announcements |
| สลิปบริจาค | `admin.game-support.index` (`/admin/game-support`) | the slip review above, unchanged rules; shows the in-game items each donation grants |
| ไอเท็มผู้สนับสนุน | `admin.gameshub.items` | per-game item catalogue, manual grants (by member e-mail, reason required), code lookup by code / e-mail / name, revoke |
| รีวิว | `admin.gameshub.reviews` | approve / reject (reason required) / pin, one by one or in bulk |
| ความเห็น | `admin.gameshub.comments` | publish / hide with an optional team note, one by one or in bulk |
| เกม · รางวัล · ฮีโร่ | `admin.gameshub.games` | campaign details and reward tiers (JSON) plus the hub hero order / hidden flag |
| ประกาศบนฮับ | `admin.gameshub.announcements` | notices shown above the hub's hero, with optional start / end (entered in Thai time, stored UTC) |

### Donation → in-game items

1. Add the item under ไอเท็มผู้สนับสนุน. `key` is what the game's code checks (`a-z 0-9 - _`), and never changes once saved; deactivate instead of deleting. `max_devices` = how many devices one code unlocks.
2. Name the item in a tier: `{"minimum": 300, "name": "SALVAGER", "rewards": ["…"], "items": ["founder-badge"]}`. Unknown keys are refused.
3. A donation snapshots the tier including item keys and names. Approving the slip creates one `game_entitlements` row per item **in the same transaction** with an 80-bit code (`XG-XXXX-XXXX-XXXX-XXXX`, Crockford base32). The unique `(game_donation_id, game_item_id)` index makes a second approval impossible to double-grant. Voiding the donation revokes its codes.
4. The member sees codes on `/games-support/my-items` and in "รายการของฉัน" on the game page. Codes are stored encrypted (shown to the owner and admins) plus a SHA-256 hash for lookup.
5. The game redeems: `POST /api/gameshub/redeem` `{game?, code, device}` (without `game` — the hub's own redeem dialog — any game's code is accepted and the answer names the game in `game` / `game_name`) — open CORS, no cookies, `throttle:20,1`. Answers `200 {ok, item{key,name,kind,description,image_url}, devices_used, devices_max, already_on_this_device}` or `404 invalid_code`, `409 wrong_game`, `409 device_limit`, `410 revoked`, `422 invalid_request`, each with a Thai `message`. The same device asking again does not use another slot. Device ids and IPs are stored only as HMAC hashes.
   Browser games can include `https://xgameshub.xman4289.com/sdk/xman-items.js` (`XmanItems.forGame(id).redeem(code)` / `.owns(key)`). Games that share this database (e.g. Krungsri) may read `game_entitlements` directly. Items should stay cosmetic — titles, badges, skins, passes — never paid power.

### Reviews

Player reviews use the shared polymorphic `reviews` table with `reviewable_type = App\Models\GameCampaign`. One review per member per game (`POST /games-support/{slug}/review`, own `throttle:5,60,gs-review` key). The stars also upsert `game_ratings`, so the average updates at once; the text shows only after approval. Editing sends it back to pending and unpins it. Public names are shortened (`Somchai J.`); e-mails never leave the server. The existing `/admin/reviews` page also lists these rows.

### What the static hub reads

All three are public, aggregate, CORS-limited to `GAME_SUPPORT_HUB_ORIGIN`, `Cache-Control: public, max-age=60/30`, and skip the session middleware (no cookies, no session files per visitor):

- `/games-support/summary.json` — totals per game (now also `review_count`).
- `/games-support/hub.json` — `{announcements[≤3 live], hero: {order: [slugs by hero_rank], hidden: [slugs]}}`.
- `/games-support/{slug}/reviews.json` — average, counts, the 30 newest approved reviews (pinned first) and `write_url`.

The hub falls back to its built-in content when any of these fail.

### Deploy

Run `php artisan migrate --force` (migration `2026_10_09_100000_create_gameshub_control_tables`: new `game_items`, `game_entitlements`, `game_item_redemptions`, `gameshub_announcements`; adds `hero_rank`, `hero_hidden` to `game_campaigns` and `moderation_note` to `game_comments`; nothing is dropped or rewritten). The public pages (`resources/views/game-support/layout.blade.php`) now use the hub's frame — sidebar + top bar linking back to xgameshub — and its palette (violet-black, lime, gold); `css/game-support.css` was rewritten for it (linked with `?v=4`, bump on every edit: assets are cached) and `images/gameshub/logo-v2.webp` is the hub's logo. Tests: `tests/Feature/GamesHubControlTest.php`.
