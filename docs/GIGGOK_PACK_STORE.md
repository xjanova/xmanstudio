# GigGok Pack Store

Avatar packs — outfits, extra secretaries and 3D stage props — sold to the
GigGok Android app (repo `xjanova/videogirl`). Admins add and price them from
`/admin/packs`; the app lists them, reports what the buyer owns, and downloads
the file behind a signed link.

The app-side half of this contract is documented in the app repo at
`docs/pack-store.md`.

## A pack is a product

Nothing here reimplements pricing, orders or licensing. A pack **is** a
`Product`, so it inherits all of it:

| Thing | Where it lives |
|---|---|
| Price, name, description, on/off sale | `products` |
| The `.zip`, its size and hash | `product_versions` |
| Who owns it | `license_keys` (a row per user per product) |
| Who downloaded what, when | `download_logs` |
| Pack id, kind, prerequisite, preview | `avatar_packs` |

`avatar_packs` holds only what a normal product has no place for. The admin
screen writes all three rows from one form, because "add an outfit and price
it" should not require knowing that.

## Endpoints

All under `throttle:60,1`.

| Route | Auth | Purpose |
|---|---|---|
| `GET /api/packs` | none | Catalogue. Public so a non-buyer can see what exists. |
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
