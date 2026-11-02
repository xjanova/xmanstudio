# GigGok Pack Store

Avatar packs — outfits, extra secretaries and 3D stage props — and knowledge
packs for Mind, sold to the GigGok Android app (repo `xjanova/videogirl`).
Admins add and price them from `/admin/packs`; the app lists them, reports
what the buyer owns, and downloads the file behind a signed link.

The app-side half of this contract is documented in the app repo at
`docs/pack-store.md`, and the knowledge pack format at
`docs/knowledge-packs.md`.

## A pack is a product

Nothing here reimplements pricing, orders or licensing. A pack **is** a
`Product`, so it inherits all of it:

| Thing | Where it lives |
|---|---|
| Price, name, description, on/off sale | `products` |
| The `.zip`, its size and hash | `product_versions` |
| Who owns it | `license_keys` (a row per user per product) |
| Who downloaded what, when | `download_logs` |
| Pack id, kind, prerequisite, preview, one-line summary | `avatar_packs` |

`avatar_packs` holds only what a normal product has no place for. The admin
screen writes all three rows from one form, because "add an outfit and price
it" should not require knowing that.

## Endpoints

All under `throttle:60,1`.

| Route | Auth | Purpose |
|---|---|---|
| `GET /api/packs` | none | Catalogue. Public so a non-buyer can see what exists. Knowledge packs only with `?with=knowledge`. |
| `GET /api/packs/mine` | Bearer license key | `{"owned": ["pack-id", …]}` |
| `POST /api/packs/{pack}/download` | Bearer license key | `{"url", "sha256", "expiresIn"}` |
| `GET /api/packs/file/{version}` | `signed` middleware | The bytes. |

### The bearer token is a license key, not a session

The app has no login screen and is not getting one. It sends the license key
it was activated with; that key names the buyer, and what the buyer owns is
looked up from there.

Which product's license counts is `config('packs.app_product_slug')`, default
`giggok`. **If no product with that slug exists, nobody authenticates.** That
is deliberate: the alternative — accepting a license for any product — would
let a key bought for something else read this user's library.

### Catalogue fields

Each item: `id`, `name {th, en}`, `kind`, `price`, `currency`, `sizeBytes`,
`preview`, `requires`, `price_tpix`, and two added with knowledge packs that
every kind carries:

| Field | |
|---|---|
| `version` | The version of the zip on sale, from its `product_versions` row — the same row that holds the file and its hash, so the two cannot drift. `null` while no file is uploaded. For a knowledge pack it was read out of the zip's `manifest.json`; the app compares it with the installed pack and offers an update when they differ. |
| `summary` | `{th, en}`, one line under the name. Either language falls back to the other; `null` when the admin wrote none. |

### Why the catalogue and the owned list are separate calls

The catalogue barely changes and can be cached hard. Ownership changes the
second a payment clears. Serving them together would make the whole response
uncacheable in order to keep the half that moves correct.

## 🔴 The download link must stay signed

If the `.zip` sat at a fixed public URL, paying would be optional: the first
buyer could paste the link anywhere and everyone downloads free, forever, and
we would never know it happened.

So the file is never web-readable. `POST /api/packs/{pack}/download` checks
the license, then returns a `temporarySignedRoute` valid for
`config('packs.download_link_minutes')` (default 10). An unsigned or stale
link is rejected by the framework before it reaches the controller.

`sha256` is returned alongside; the app verifies the bytes it received.

## Uploading a pack

`/admin/packs` → create → upload `.zip`.

Before storing anything, the upload is opened and the `id` inside its
`pack.json` is compared with the pack id being sold. **They are two different
things that must agree.** The app installs by the id in `pack.json` and
reports ownership by the id in the catalogue; if they drift, a customer pays,
the download succeeds, and the app quietly fails to recognise what it just
installed — with no error anywhere. The upload is refused instead.

A zip wrapped in one folder (what right-clicking a folder on Windows produces)
is accepted — the app unwraps that itself.

Only the newest upload stays active. The app has no version picker, so an
older version left active would be a coin flip over which file ships.

A knowledge pack is a different format — `manifest.json`, not `pack.json` —
and has its own checks, below.

## Knowledge packs (`kind: "knowledge"`)

Text for Mind to search on the phone, sold in the app's "ความรู้" tab:
`manifest.json` plus markdown under `content/` (any depth — the sample pack
keeps `content/th/` and `content/en/`) and illustrations, never code. The app searches enabled
packs on the device for every chat message, so they work with every brain,
offline included. Same product, same three endpoints, same signed link, same
lifetime license per pack as an outfit — nothing parallel was built.

### 🔴 Hidden from apps that cannot install them

`GET /api/packs` lists knowledge packs only when the request carries
`?with=knowledge` (a comma list; anything that is not a plain string opts into
nothing). The app release with the knowledge tab always sends it. Older
releases read any kind they do not know as `character`, show it among the
outfits, and fail to install it — after the customer has paid. `/mine` and
`/download` need no such switch: an old app never learns a knowledge pack's id.

### 🔴 The upload refuses anything the app would refuse

The app checks every pack before installing it (`lib/store/knowledge_pack.dart`).
If the shop did not check too, a pack the app rejects could go on sale and a
customer would pay for an install error. `App\Services\KnowledgePackValidator`
opens the zip (in memory, never extracted) and refuses it — nothing stored,
the reason shown next to the upload button — when:

| Rule | Limit |
|---|---|
| `manifest.json` `id` | must equal the pack id on sale |
| `kind` | must be `knowledge` |
| `version` | required, a JSON **string**. `1.0` as a number reaches PHP as `1` and the app as `"1.0"`; the update button would never go away |
| `category` | required, one of `survival first_aid engine cooking trading general` (the app would quietly file anything else under `general`, outside every safety rule) |
| Files | only `manifest.json`, `*.md *.markdown *.txt`, `*.png *.jpg *.jpeg *.webp`. `__MACOSX/`, `.DS_Store`, `Thumbs.db` are skipped as the app skips them |
| Paths | none with a `..` segment, a leading `/`, a `:`, or a symlink. Backslashes count as `/` |
| Zip | ≤ 8 MB; declared unpacked total ≤ 24 MB |
| Text files | each ≤ 512 KB actually read (a zip's declared sizes can lie), and UTF-8 — Notepad's old "ANSI" (TIS-620) would install as garbage nobody can search |
| `manifest.json` | ≤ 64 KB, a JSON object |
| `content/` | at least one section of text, in `content/` or any folder under it; ≤ 400,000 characters, counted on the sections the app cuts markdown into and in UTF-16 units as Dart counts them |
| Images | every `*.png *.jpg *.jpeg *.webp` anywhere in the zip (the app installs and draws them): ≤ 120 per pack, each ≤ 2 MB actually read, really a PNG, JPEG or WebP by its bytes (`getimagesizefromstring` — a GIF or a script renamed `.png` is refused; a PNG named `.jpg` is fine, the app does not care which of the three), ≤ 4096 px per side. The side matters more than the bytes: a flat-colour PNG of 2 MB can declare 30000×30000 and unpack to gigabytes on the phone |
| `persona` | ≤ 300 characters per language |
| `safety` | ≤ 4 rules per language, each ≤ 160 |
| `starters` | ≤ 6 per language, each ≤ 120 |

Where the app trims instead of refusing (persona, rules, starters) the upload
still refuses: an admin can fix the manifest, a customer cannot.

One wrapper folder (Windows right-click → zip) is unwrapped, as the app does.

### The version comes from the file

The admin does not type a version for a knowledge pack: it is read from
`manifest.json` and stored on the version row, which is what the catalogue
serves. A **different** file under a version already uploaded is refused —
the app only offers an update when the version changes, so the new content
would reach new buyers and never anyone who had installed it. The same bytes
again are accepted.

### Changing the kind

Switching an existing pack between knowledge and any other kind is refused
once a file has been uploaded: the file on sale would be in the wrong format
for its new kind. Before the first upload it is allowed, so a mis-picked kind
can be fixed. Create a new pack instead.

A knowledge pack id must also start with `a-z` or `0-9`: the app refuses ids
starting with `_` (its own file names) or `.`.

### Not checked by code — a person has to

The admin form says this in Thai, next to the kind picker:

- Content where a mistake hurts someone (first aid, survival, engines) is
  read by a person before sale; every topic ends with `ที่มา:` and every
  number is checked against its source.
- **An investing pack must declare `category: "trading"`.** The app then adds
  its own rules (no buy/sell calls, no price targets, "not investment advice").
  An investing pack filed as `general` escapes all of them, and no code here
  can tell what a pack is about.
- The content is ours or licensed.

## Buying

`GET /shop/{pack}` is the link the app opens in a browser. It looks the pack
up by pack id and **redirects to the normal product page** — deliberately a
redirect rather than a second storefront, because the product page already
has the cart, coupons, every payment method, and the license issuing that
follows a completed order. A parallel page would be a second thing to keep
correct and would silently miss every fix the real one gets.

No login is required to reach it. Someone arriving from the app has not
signed in yet, and bouncing them to a login page before they can see the
price loses the sale. Checkout itself is behind `auth`, as it always was.

Paying happens on the web rather than in the app because GigGok ships through
GitHub Releases, not Play — Play's billing rules apply to apps on Play. If it
ever goes on Play this needs rethinking, not just moving a button.

### 🔴 A pack license never expires

`LicenseService::generateLicensesForOrder()` defaults to a **yearly** license.
On a pack that would take something the customer bought outright off their
device a year later: it drops out of `owned[]` the moment it expires, and
nothing anywhere explains why.

So a product with an `avatar_packs` row gets `lifetime` instead. An explicit
`license_type` in the order item's `custom_requirements` still wins, and
every non-pack product is untouched.

## Storage

`config('packs.disk')`, default `local` — i.e. `storage/app`, not the web
root. Nothing serves these files except the signed route.

Existing products download from GitHub releases and still do; `storage_path`
on `product_versions` is what marks a version as one of ours instead.

## Decisions that were open, and how they landed

1. **Where the zip lives** — our own disk behind a signed route, not S3/R2.
   Laravel signs URLs natively, so no new storage service and no new secret.
2. **One license for everything, or one per pack** — one per pack, matching
   how every other product here works. `owned[]` is simply the set of pack
   products the user holds an active license for.
3. **Refunds** — setting a license to any status other than `active` drops
   the pack out of `owned` on the next call. Whether the app then deletes the
   downloaded files is the app's decision, not this API's.

## Tests

`tests/Feature/PackStoreTest.php` — the API, with the paywall covered from
every angle (no license, wrong product's license, expired license, unpaid
pack, unknown pack, free pack, unsigned link, expired link).

`tests/Feature/AvatarPackAdminTest.php` — the admin screens, including the
zip id check. These also stand in for a Blade compile check: a view that will
not compile, or a `route()` name that does not exist, fails there rather than
the first time an admin opens the page.

`tests/Feature/PackPurchaseTest.php` — the buy link, the lifetime license,
and the whole round trip: pay, license issued, the app reports the pack as
owned, the download works.

`tests/Feature/KnowledgePackStoreTest.php` — knowledge packs: the
`?with=knowledge` switch, `version` and `summary`, every upload refusal in the
table above (one data-provider case each), packs right at the limits
(120 images, a 4096 px side, content only in `content/th/` and `content/en/`),
the version taken from the manifest, the kind switch, a free pack downloading with no `Authorization`
header, a paid one needing ownership, and the lifetime license.
