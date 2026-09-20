# Site Artwork

All decorative imagery on the public site is generated in-house and served from
`public_html/artwork/`. **There are no external image hotlinks anywhere in the
public views** — the site used to pull ~15 Unsplash photos at render time, which
meant an external dependency on every page load and a generic stock look.

## Using it

Drop the artwork layer into any hero. The parent must be `position: relative`,
and **the sibling content must be positioned too** (`class="relative"`), or the
absolutely-positioned artwork will paint over it.

```blade
<section class="relative overflow-hidden ...">
    <x-page-art art="hero-about" :opacity="45" />
    <div class="relative ...">   {{-- `relative` is required --}}
        ...
    </div>
</section>
```

| prop | default | notes |
|---|---|---|
| `art` | `hero-network` | filename in `public_html/artwork/` without `.webp` |
| `opacity` | `30` | 0–100, clamped |
| `position` | `center` | any `background-position` value |
| `scrim` | `true` | radial darkening so overlaid text stays readable |

For content images, reference the file directly and lazy-load below-the-fold ones:

```blade
<img src="{{ asset('artwork/card-ai.webp') }}" loading="lazy" decoding="async" alt="...">
```

## The set

Heroes are 1920w, cards 1200w, all WebP q82 (~2.4 MB for 25 files).

**Heroes:** `hero-home` `hero-about` `hero-portfolio` `hero-services` `hero-support`
`hero-team` `hero-products` `hero-rental` `hero-tracking` `hero-changelog`
`hero-academy` `hero-legal` `hero-metalx` `hero-network` `hero-gpuxmine` `hero-kyc`
`hero-quote` `quote-doc` `hero-domains`

`hero-kyc` is deliberately **abstract** — a glowing shield, a fingerprint, a padlock,
blank floating sheets. No ID card, no face, no lettering. That page asks for a real
Thai national ID number, and artwork that resembles a real document reads as a
worked example of one.

`hero-quote` and `quote-doc` are **generated but not wired up yet** — they are for the
redesign of the quotation builder, which is still a proposal. `hero-quote` is 1920×1080
and holds its negative space in the upper centre; `quote-doc` is 1600×1000 and is a
content image (a paper stack lit from one side), so reference it with `<img>`, not
`<x-page-art>`. Both drop the usual magenta: that page is where a customer decides to
spend money, so it stays in the blue `#3B82F6` → cyan half of the palette, which is the
one the design system leads with.

**Cards:** `card-blockchain` `card-web` `card-mobile` `card-ai` `card-iot`
`card-security` `card-software` `card-flutter` `card-design` `card-marketing`
`card-studio` `card-domains`

`hero-academy` is generated but unused: `/code-academy` is a deliberately *light*
(blue/cream) theme and dark artwork clashes with it. The **retro** theme is also
left alone — it is a self-contained Tron/art-deco design with its own visual
language (gold foil, 80s vector grid) that photographic art would muddy.

## Regenerating — do this in the browser, not over the API

The Magnific/Freepik plan's "unlimited" is a **web-app entitlement**, not an API
one. The connector reports `isUnlimitedMode: true` (the account) alongside
`unlimitedAppliesHere: false` (the session) — read the second one. Generating the
identical job through the connector **charges credits**; generating it in the web
app costs **nothing**. The transport sets the price, not the model.

So:

1. **Submit** at `magnific.com/app/ai-image-generator` in a logged-in browser.
   The Generate button literally reads "Generate · Unlimited" there.
2. **Retrieve** over the read API (`creations_search` → `creations_wait`), which
   is free, rather than scraping signed CDN URLs out of the page.
3. **Compress** before committing — raw renders are 200–570 KB each:

```bash
ffmpeg -i raw.jpg -vf "scale=1920:-2:flags=lanczos" -c:v libwebp -quality 82 -compression_level 6 out.webp
```

Style prompt that matches the site's DNA (taken from the X-DREAMER logo — deep
near-black navy, cyan→violet→magenta neon, volumetric glow, digital particles):

> `... Deep near-black navy void, luminous cyan violet and magenta light,
> volumetric bloom, drifting digital particles, premium dark futuristic
> aesthetic, open negative space in the centre, no text, no letters, no logo,
> ultra detailed, 8k`

Always ask for **negative space in the centre** on heroes — that is what keeps the
headline readable — and always negate text/letters/logos, or the model renders
garbled lettering into the art.

## Putting a hero on a *member-area* page — two traps

The customer portal has two layouts and `ThemeService::getCustomerLayout()` picks
between them. `layouts/customer-premium.blade.php` ships its own
"Premium Content Dark Mode Overrides" stylesheet that rewrites plain Tailwind
utilities with `!important`. Two of those rules quietly break light-tinted
callouts:

```css
.bg-white { background: rgba(30, 27, 75, 0.6) !important; }

.bg-gradient-to-r.from-red-50,
.bg-gradient-to-r.from-amber-50,
.bg-gradient-to-r.from-yellow-50 { background: rgba(30, 27, 75, 0.8) !important; }
```

The backgrounds are forced dark but the **text colours are not touched** — only
`.text-gray-*` is remapped. So `bg-gradient-to-r from-red-50 … text-red-800`
renders as red-800 on near-black and is effectively unreadable, and
`bg-white text-slate-900` becomes an invisible dark-on-dark chip. Both were real
defects on `/kyc` (the rejection reason, and the "you are here" step marker)
before they were caught by rendering the page rather than by reading it.

Write callouts as `bg-red-50 dark:bg-red-500/10 … text-red-900 dark:text-red-200`
instead. `html` carries the `dark` class in the portal, so the `dark:` variant is
what actually paints, and the light pair still covers the standard layout. Opacity
variants (`bg-white/80`, `bg-white/10`) are a different class name and escape the
override entirely.

Second trap: **the CSS is prebuilt.** `public_html/build/` is gitignored and
Tailwind only emits the utilities it finds in source at build time, so a brand-new
class (`lg:w-72`, `tracking-[0.2em]`, …) does nothing until `npm run build` runs.
A hero that collapses into a one-word-per-line column is this, not a flex bug.
