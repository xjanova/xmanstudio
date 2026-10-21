# XMAN STUDIO brand files (2026-10)

The migration `2026_10_04_120000_install_xman_studio_brand_2026_10` copies these onto the public
disk and points the settings at them. After that, the Branding page (`/admin/branding`) and the SEO
page own them: an upload there replaces the copy, not these files.

| File | Setting | Used on |
|---|---|---|
| `xman-logo-light.webp` | `site_logo` | light grounds: light-mode nav, login/register, member area, PDF documents |
| `xman-logo-dark.webp` | `site_logo_dark` | dark grounds: 3D universe, dark mode, footers, admin, Nova, e-mail, OG generator |
| `xman-mark.png` | `site_favicon` | favicon.ico, app icons, schema.org Organization logo (via `/favicon-512.png`) |
| `xman-og.jpg` | `seo_settings.og_image` | the 1200×630 image shown when a page is shared or listed |

Every view asks `App\Support\BrandLogo` for the variant its background needs; e-mail and DomPDF get
a PNG copy of it (Outlook shows no WebP, DomPDF drops WebP transparency).

**How they were made.** The glossy X emblem and the lettering were generated in the owner's
ChatGPT ("Create Logo Image" chat, transparent PNGs, 1672×941). The X emblem replaces the X of
XMAN; MAN sits on top, a cyan → violet → magenta thread with a node at each end divides it from
S T U D I O, which is spread to MAN's width (the owner's layout). The emblem was cut from the
lettering it touched by colour (the letters are flat white), the lettering upscaled ×2 with its
alpha edge re-sharpened, and the light variant is the same lockup with the letters in slate-900
`#0F172A`. The share image is the dark logo over a ChatGPT nebula, its Thai line set in Sarabun
and rendered by headless Chrome (GD cannot shape Thai).
