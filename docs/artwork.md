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
`hero-academy` `hero-legal` `hero-metalx` `hero-network`

**Cards:** `card-blockchain` `card-web` `card-mobile` `card-ai` `card-iot`
`card-security` `card-software` `card-flutter` `card-design` `card-marketing`
`card-studio`

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
