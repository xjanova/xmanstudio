# Reselling domains and VPS through the Hostinger API

What we sell on top of our own Hostinger account, how the money moves, and the traps that
cost money when they are forgotten. Written 2026-09-23 after the audit that added VPS rental.

## What the API can and cannot resell

The public API manages **our own account**. There is no customer, sub-account or reseller
entity: everything is bought with the card saved on our account and handed to the customer.

| Product | Buy via API | Set up via API | Sold here |
|---|---|---|---|
| Domains (+ DNS, forwarding, lock, EPP) | yes | yes | `/domains` |
| VPS (KVM 1/2/4/8, game-panel sizes) | yes (`POST /api/vps/v1/virtual-machines`, buys **and** installs) | yes | `/vps` |
| Business e-mail | yes (catalogue `EMAIL`) | **no** — nothing completes a `pending_setup` mail order, and a mail order carries no subscription id | not sold |
| Hosting, WordPress, Ecommerce, Horizons, Reach | **no purchase endpoint** | only inside plans bought in hPanel | not sold |
| Agency hosting | no | sites on an Agency plan bought in hPanel | not sold (possible later: host client sites on our own plan) |

The catalogue for our Thai account is **THB, in satang** (`45900` = 459.00 ฿). Price ids are
per period (`hostingerinth-vps-kvm2-thb-1m`) and opaque — copy them from the catalogue, never build them.

## Money flow

1. Customer pays **us** from the site wallet (price = cost × (1 + margin), rounded up).
2. We buy upstream with **our** card (`payment_method_id` omitted → the account default).
3. Renewals: **ours**, from the customer's wallet. Upstream auto-renewal is switched **off** on
   every subscription we resell (`markActive()` and `hostinger:billing-check`). Left on, it charges
   our card at expiry — twice for a customer who renews with us, a free year for one who did not.
   Never touch subscriptions that are not linked to a customer row: the owner's own domains and
   servers live in the same account.

## When our card fails (the "insufficient balance" path)

- A refused purchase or renewal (`402`, or words like payment/insufficient/declined in the body) →
  the customer is refunded, **sales pause for 30 minutes** (`UpstreamBilling::pause()`), and a
  CRITICAL card goes to Telegram (`BusinessAlerts::domainPurchaseRefused` / `vpsPurchaseRefused`,
  one per day). While paused, both shops refuse **before** debiting, with a "try again later" message.
- `hostinger:billing-check` (every 6 h) reads the payment methods: none, no default, default
  expired/suspended → CRITICAL + pause; default expiring within 30 days → WARNING. A healthy check
  lifts only a pause it set itself — a declined card looks healthy from the method list.
- The API has **no balance figure**: "not enough money on the card" is only ever learned from a refusal.
- If Telegram is not configured, these CRITICAL alerts go by **e-mail** to `contact_email` and every
  super admin (`BusinessAlerts::provider()` → `AdminAlertMail`). Production still had no bot on
  2026-09-23 (`admin_alerts` empty) — every refusal card before this fallback went nowhere.
- Admin: `/admin/domains` and `/admin/vps` show the payment methods, the pause, and a "resume" button.

## Traps (each was a real bug)

- **202 is not "done".** A 202 purchase means payment is still clearing and the product was **not**
  set up. A domain then lands in the portfolio as `pending_setup` (paid, registered to nobody) and
  must be finished with `POST /portfolio/{domain}/setup`; a VPS lands in state `initial` and needs
  `POST /virtual-machines/{id}/setup`. The reconciliation jobs do both. A 202 **renewal** is not a
  renewal yet either: the paid-until date only moves once upstream's does. Orders and renewals
  upstream acknowledged with a 202 are waited for a **day** before a refund (the charge may clear
  late); ones whose answer was lost get 45 (VPS) / 90 (domain) minutes.
- **A timeout may have gone through — and so may a 5xx.** `lastOutcomeUnknown()` is true for a
  transport failure after the request left, and for any 5xx (upstream can fall over after taking the
  order). Those orders are never refunded on the spot; the reconciliation job looks them up. A
  connection refused / DNS failure (cURL 7/6) never left and is refunded at once (`'unreachable'`).
- **Only POSTs are never retried.** The HTTP client retries reads on a dropped connection; a
  write is sent exactly once. Laravel counts a timeout AFTER sending as a connection failure too, so
  retrying a purchase was a second domain or server on our card.
- **"Could not look" is not "not there".** A refund needs a *definitive* not-found: the full
  portfolio / VM / subscription list was read and the item is not in it. A failed lookup (timeout,
  our rate limiter, 5xx) decides nothing — after the window it pages the admin instead.
- **Matching a lost order to what upstream built** goes subscription id → the customer's hostname on
  a machine made since the order → the ONE uninstalled machine of that plan made since the order
  (a 202 machine still wears `srvNNN.hstgr.cloud`). Two candidate machines, or another waiting
  order that could claim the same one, is `ambiguous`: no adoption, no refund, an alert — adopting
  the wrong machine hands one customer another's server. Domains: another live/settling order for
  the same name is ambiguous the same way.
- **Renewals are settled against the date they were paid against** (`previous_expires_at` on the
  renewal row), never the current one — the daily sync copies the new date over as soon as the
  renewal lands, and "later than itself" is never true.
- **An accepted VPS setup is not re-sent** for 20 minutes (upstream keeps saying `initial` for a
  while); only refusals count towards the 3-attempt limit, and one reconcile per order runs at a time
  (the customer's page polls it too).
- **Refunded but bought.** Auto-renewal is switched off on every subscription any row ever held,
  refunded ones included. `hostinger:billing-check` also reports machines in `initial` that no order
  claims (usually a 202 that cleared after its refund) — reported, never touched, since the owner's
  own servers share the account.
- **Expired domains renew for 25 days only** (`DomainRegistration::RENEW_GRACE_DAYS`). After that
  the registry's restore fee would land on our card while the customer paid the ordinary price.
- **Double submit.** Only the request that created the row may call upstream (`[$row, $created]`
  from `debitAndReserve`). A duplicate calling too could get "not available" back and refund a live
  domain. A refunded attempt gets a new idempotency key (`refundedAttemptsToday`), so retries work.
- **Money spent upstream is not refunded on a timer.** A VPS that will not build goes to `failed`
  and an admin decides (`/admin/vps` → retry or refund). Only orders that provably never reached
  the supplier are auto-refunded.
- **Root passwords never touch the database.** For a delayed install the password waits in the
  cache, encrypted, for at most 24 h; order forms never flash it back (`$request->except('root_password')`).
- **Never show the supplier's name to customers**: templates mentioning it are dropped, the default
  `srvNNN.hstgr.cloud` hostname never replaces the customer's, plan names are ours (`VPS Business`,
  not `KVM 2`), `last_error` is hidden from serialisation.
- **First period vs renewal.** VPS and many TLDs are much dearer from the second period. Every price
  on the site says which one it is ("ปีแรก / ปีต่อไป", "เดือนแรก / เดือนถัดไป"), and renewals are
  charged at the renewal cost — never the first-period price again.

## The customer's VPS panel (`/my-account/vps/{id}?tab=…`)

Six tabs — overview, network & security, backups, system, activity, billing. Each tab is a plain
link and fetches only what it shows (`Customer\VpsController::show()`): the whole site shares 90
supplier calls a minute, so the panel never loads everything at once. Live machine details are
cached 20 s (`vps.vm.{id}`), metrics 5 min, firewall/actions 1 min, keys 5 min, malware 10 min.

- **Firewall**: one per rental, created on first use, its id kept in `vps_instances.remote_firewall_id`.
  Customer requests only ever touch that id (never one from a form). Firewalls drop everything no rule
  accepts, so a new one is seeded with SSH + HTTP + HTTPS before activation, and any rule set without
  SSH needs an explicit "block SSH on purpose" tick (`VpsFirewall::allowsSsh()`).
- **SSH keys** are account-level upstream: stored as `vps{id}-{name}` and attached to the one machine;
  the panel lists the machine's attached keys only. The API cannot detach a key — the page says to
  remove it from `~/.ssh/authorized_keys`.
- **Recovery mode / panel password / root password**: passwords go straight to the API, are in
  `dontFlash`, and the API client redacts those paths from its logs.
- Each VPS route has its own throttle prefix (`throttle:N,M,vps-…`): a bare `throttle:N,M` is keyed on
  the user alone, so every such route in the app shares one counter.

## Schedule (routes/console.php)

| Command | When | Does |
|---|---|---|
| `domains:reconcile` | every 5 min | finish/settle/refund unsettled domain orders and renewals |
| `vps:reconcile` | every 5 min | install 202 machines, adopt timed-out purchases, settle renewals |
| `hostinger:billing-check` | every 6 h | payment-method health, pause/resume, stray auto-renewals |
| `domains:sync-status` | 05:00 | mark expired domains, link missing subscription ids |
| `domains:renew` / `vps:renew` | 09:30 / 09:40 | notices, wallet renewals, reminders, expiry |
| `domains:sync-catalogue` / `vps:sync-catalogue` | 04:00 / 04:10 | real prices from the catalogue |
