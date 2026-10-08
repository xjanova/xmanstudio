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
